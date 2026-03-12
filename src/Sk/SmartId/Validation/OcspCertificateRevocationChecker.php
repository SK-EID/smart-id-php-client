<?php

/*-
 * #%L
 * Smart ID sample PHP client
 * %%
 * Copyright (C) 2018 - 2026 SK ID Solutions AS
 * %%
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 * #L%
 */

declare(strict_types=1);

namespace Sk\SmartId\Validation;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use phpseclib3\File\ASN1;
use phpseclib3\File\ASN1\Maps\Name;
use phpseclib3\File\X509;
use phpseclib3\Math\BigInteger;
use Sk\SmartId\Exception\ValidationException;

/**
 * Checks certificate revocation status using OCSP (Online Certificate Status Protocol).
 *
 * Extracts the OCSP responder URL from the certificate's Authority Information Access (AIA) extension,
 * sends an OCSP request, and verifies the certificate has not been revoked.
 *
 * Uses phpseclib3 for all certificate parsing and ASN.1 encoding/decoding.
 *
 * TODO: OCSP revocation checking has not been verified against the production environment.
 *   The demo OCSP responder at aia.demo.sk.ee intentionally reports test certificates as revoked,
 *   so only production can confirm correctness. Use configureValidator() (without OCSP) for demo
 *   and configureValidatorWithOcsp() for production.
 *
 * Known limitations (see Smart-ID documentation "Response verification" for full requirements):
 * - CRL fallback: If OCSP fails or is unavailable, the docs recommend falling back to CRL checking.
 *   This implementation does not perform CRL checking; OCSP failure results in a ValidationException.
 * - OCSP responder chain verification: The docs state that "OCSP responses are digitally signed.
 *   The certificate chain of the OCSP responder itself should also be verified." This implementation
 *   does not independently verify the OCSP responder's certificate chain against trusted CAs.
 */
class OcspCertificateRevocationChecker
{
    private const OCSP_REQUEST_CONTENT_TYPE = 'application/ocsp-request';
    private const OCSP_RESPONSE_CONTENT_TYPE = 'application/ocsp-response';

    private Client $httpClient;
    private int $timeoutSeconds;

    public function __construct(?Client $httpClient = null, int $timeoutSeconds = 10)
    {
        $this->httpClient = $httpClient ?? new Client();
        $this->timeoutSeconds = $timeoutSeconds;
    }

    /**
     * Check revocation status of the given end-entity certificate against its OCSP responder.
     *
     * @param string $subjectCertPem PEM-encoded end-entity certificate
     * @param string $issuerCertPem PEM-encoded issuer (CA) certificate
     * @throws ValidationException if the certificate is revoked, OCSP check fails, or OCSP URL is not found
     */
    public function checkRevocationStatus(string $subjectCertPem, string $issuerCertPem): void
    {
        try {
            $subjectCert = $this->parseCertificate($subjectCertPem, 'subject');

            $ocspResponderUrl = $this->extractOcspUrl($subjectCert);
            if ($ocspResponderUrl === '') {
                throw new ValidationException(
                    'Certificate does not contain an OCSP responder URL in the Authority Information Access extension',
                );
            }

            $issuerNameDer = ASN1::encodeDER($subjectCert['tbsCertificate']['issuer'], Name::MAP);
            $issuerPublicKeyBytes = $this->extractRawPublicKeyBytes($issuerCertPem);
            $serialNumber = $subjectCert['tbsCertificate']['serialNumber'];

            $requestBody = $this->buildOcspRequest($issuerNameDer, $issuerPublicKeyBytes, $serialNumber);
            $responseBody = $this->sendOcspRequest($ocspResponderUrl, $requestBody);

            $this->parseOcspResponse($responseBody);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new ValidationException(
                sprintf('OCSP check failed: %s', $e->getMessage()),
            );
        }
    }

    /**
     * Build a DER-encoded OCSP request body.
     *
     * Structure per RFC 6960:
     * OCSPRequest -> tbsRequest -> requestList -> Request -> CertID {
     *   hashAlgorithm (SHA-1), issuerNameHash, issuerKeyHash, serialNumber
     * }
     */
    private function buildOcspRequest(string $issuerNameDer, string $issuerPublicKeyBytes, BigInteger $serialNumber): string
    {
        $issuerNameHash = sha1($issuerNameDer, true);
        $issuerKeyHash = sha1($issuerPublicKeyBytes, true);
        $serialBytes = $serialNumber->toBytes(true);

        // SHA-1 AlgorithmIdentifier: SEQUENCE { OID 1.3.14.3.2.26 }
        $hashAlgorithm = $this->derSequence(
            $this->derOid("\x2B\x0E\x03\x02\x1A"), // SHA-1 OID
        );

        // CertID: SEQUENCE { hashAlgorithm, issuerNameHash, issuerKeyHash, serialNumber }
        $certId = $this->derSequence(
            $hashAlgorithm
            . $this->derOctetString($issuerNameHash)
            . $this->derOctetString($issuerKeyHash)
            . $this->derInteger($serialBytes),
        );

        // Request: SEQUENCE { CertID }
        $request = $this->derSequence($certId);

        // requestList: SEQUENCE { Request }
        $requestList = $this->derSequence($request);

        // tbsRequest: SEQUENCE { requestList }
        $tbsRequest = $this->derSequence($requestList);

        // OCSPRequest: SEQUENCE { tbsRequest }
        return $this->derSequence($tbsRequest);
    }

    /**
     * Parse the OCSP response and throw if the certificate is revoked or unknown.
     *
     * @throws ValidationException
     */
    private function parseOcspResponse(string $responseBody): void
    {
        $decoded = ASN1::decodeBER($responseBody);
        if ($decoded === false || !isset($decoded[0]['content'])) {
            throw new ValidationException('Failed to decode OCSP response');
        }

        $ocspResponse = $decoded[0]['content'];

        // Check responseStatus (ENUMERATED at index 0)
        $statusValue = $ocspResponse[0]['content'] ?? null;
        if ($statusValue === null) {
            throw new ValidationException('OCSP response missing responseStatus');
        }
        // responseStatus 0 = successful
        if ($statusValue !== "\x00" && $statusValue !== 0 && $statusValue !== '0') {
            $this->throwForOcspStatus($statusValue);
        }

        // Navigate: responseBytes [0] EXPLICIT -> SEQUENCE { OID, OCTET STRING(BasicOCSPResponse) }
        $responseBytes = $ocspResponse[1] ?? null;
        if ($responseBytes === null || !isset($responseBytes['content'])) {
            throw new ValidationException('OCSP response missing responseBytes');
        }

        // Find the inner SEQUENCE
        $innerSeq = $responseBytes['content'][0] ?? $responseBytes;
        $basicResponseDer = null;
        foreach (($innerSeq['content'] ?? []) as $el) {
            if (($el['type'] ?? 0) === 4) { // OCTET STRING
                $basicResponseDer = $el['content'];
                break;
            }
        }

        if ($basicResponseDer === null) {
            throw new ValidationException('OCSP response missing BasicOCSPResponse');
        }

        // Decode BasicOCSPResponse
        $basicDecoded = ASN1::decodeBER($basicResponseDer);
        if ($basicDecoded === false || !isset($basicDecoded[0]['content'][0]['content'])) {
            throw new ValidationException('Failed to decode BasicOCSPResponse');
        }

        // tbsResponseData -> find the responses SEQUENCE (UNIVERSAL SEQUENCE)
        $tbsResponseData = $basicDecoded[0]['content'][0]['content'];
        $responsesSeq = null;
        foreach ($tbsResponseData as $child) {
            if (($child['type'] ?? 0) === 16 && ($child['class'] ?? 0) === 0) { // SEQUENCE, UNIVERSAL
                $responsesSeq = $child;
            }
        }

        if ($responsesSeq === null || !isset($responsesSeq['content'][0]['content'])) {
            throw new ValidationException('OCSP response missing SingleResponse');
        }

        // SingleResponse: { CertID, CertStatus, thisUpdate, ... }
        $singleResponse = $responsesSeq['content'][0]['content'];
        if (count($singleResponse) < 2) {
            throw new ValidationException('OCSP SingleResponse is malformed');
        }

        // CertStatus is at index 1
        // Context-specific tags: [0]=good, [1]=revoked, [2]=unknown
        $certStatus = $singleResponse[1];
        $statusTag = $certStatus['type'] ?? -1;
        $statusClass = $certStatus['class'] ?? 0;

        // phpseclib3 uses class 2 for CONTEXT_SPECIFIC
        if ($statusClass === 2 || $statusClass === 128) {
            match ($statusTag) {
                0 => null, // good — do nothing
                1 => throw new ValidationException('Certificate has been revoked'),
                2 => throw new ValidationException('Certificate revocation status is unknown'),
                default => throw new ValidationException(
                    sprintf('Unexpected OCSP certStatus tag: %d', $statusTag),
                ),
            };
            return;
        }

        // Fallback: if class is not context-specific, interpret by structure
        // A constructed element with children at index 1 likely means revoked (RevokedInfo)
        if (is_array($certStatus['content'] ?? null) && count($certStatus['content']) > 0) {
            throw new ValidationException('Certificate has been revoked');
        }

        // Empty/null content at index 1 with low tag number could be good or unknown
        if ($statusTag === 0) {
            return; // good
        }

        throw new ValidationException('Certificate revocation status is unknown');
    }

    /**
     * @param string|int $statusValue
     * @throws ValidationException
     */
    private function throwForOcspStatus(string|int $statusValue): void
    {
        $code = is_string($statusValue) ? ord($statusValue) : $statusValue;
        $message = match ($code) {
            1 => 'OCSP responder: malformed request',
            2 => 'OCSP responder: internal error',
            3 => 'OCSP responder: try later',
            5 => 'OCSP responder: signature required',
            6 => 'OCSP responder: unauthorized',
            default => sprintf('OCSP responder returned error status: %d', $code),
        };
        throw new ValidationException($message);
    }

    // =========================================================================
    // DER encoding helpers
    // =========================================================================

    private function derSequence(string $content): string
    {
        return "\x30" . $this->derLength($content) . $content;
    }

    private function derOctetString(string $value): string
    {
        return "\x04" . $this->derLength($value) . $value;
    }

    private function derInteger(string $bytes): string
    {
        // Ensure positive integer has leading 0x00 if high bit is set
        if (strlen($bytes) > 0 && (ord($bytes[0]) & 0x80) !== 0) {
            $bytes = "\x00" . $bytes;
        }
        return "\x02" . $this->derLength($bytes) . $bytes;
    }

    private function derOid(string $encodedValue): string
    {
        return "\x06" . $this->derLength($encodedValue) . $encodedValue;
    }

    private function derLength(string $content): string
    {
        $len = strlen($content);
        if ($len < 0x80) {
            return chr($len);
        }
        if ($len < 0x100) {
            return "\x81" . chr($len);
        }
        if ($len < 0x10000) {
            return "\x82" . pack('n', $len);
        }
        return "\x83" . chr(($len >> 16) & 0xFF) . pack('n', $len & 0xFFFF);
    }

    // =========================================================================
    // Certificate helpers
    // =========================================================================

    /**
     * Parse a PEM certificate using phpseclib3.
     *
     * @param string $pem PEM-encoded certificate
     * @param string $label Label for error messages ('subject' or 'issuer')
     * @return array<string, mixed> Parsed certificate array
     * @throws ValidationException
     */
    private function parseCertificate(string $pem, string $label): array
    {
        $x509 = new X509();
        $cert = $x509->loadX509($pem);

        if ($cert === false || !isset($cert['tbsCertificate'])) {
            throw new ValidationException(
                sprintf('Failed to parse %s certificate for OCSP check', $label),
            );
        }

        return $cert;
    }

    /**
     * Extract the raw SubjectPublicKey BIT STRING bytes from a PEM certificate.
     *
     * This extracts the raw bytes from the DER-encoded certificate's
     * TBSCertificate -> SubjectPublicKeyInfo -> SubjectPublicKey BIT STRING,
     * which is what OCSP requires for the issuerKeyHash calculation.
     *
     * @param string $pem PEM-encoded certificate
     * @return string Raw public key bytes
     * @throws ValidationException
     */
    private function extractRawPublicKeyBytes(string $pem): string
    {
        // Use OpenSSL to get a clean DER export of the certificate
        $certResource = openssl_x509_read($pem);
        if ($certResource === false) {
            throw new ValidationException('Failed to read issuer certificate for OCSP public key extraction');
        }

        if (!openssl_x509_export($certResource, $cleanPem)) {
            throw new ValidationException('Failed to export issuer certificate for OCSP public key extraction');
        }

        // Convert clean PEM to DER
        $base64 = preg_replace('/-----[A-Z ]+-----/', '', $cleanPem);
        $der = base64_decode(str_replace(["\r", "\n", " "], '', $base64 ?? ''), true);

        if ($der === false || $der === '') {
            throw new ValidationException('Failed to decode issuer certificate DER for OCSP');
        }

        // Decode raw DER using phpseclib3's ASN1 decoder
        $decoded = ASN1::decodeBER($der);
        if ($decoded === false || !isset($decoded[0]['content'][0]['content'][6]['content'][1]['content'])) {
            throw new ValidationException('Failed to extract public key from issuer certificate for OCSP');
        }

        // Certificate -> TBSCertificate[0] -> SubjectPublicKeyInfo[6] -> SubjectPublicKey BIT STRING[1]
        $bitStringContent = $decoded[0]['content'][0]['content'][6]['content'][1]['content'];

        // BIT STRING content starts with an "unused bits" octet (0x00) — skip it
        return substr($bitStringContent, 1);
    }

    /**
     * Extract the OCSP responder URL from a certificate's AIA extension.
     *
     * @param array<string, mixed> $cert Parsed certificate from phpseclib3
     * @return string OCSP URL or empty string if not found
     */
    private function extractOcspUrl(array $cert): string
    {
        $extensions = $cert['tbsCertificate']['extensions'] ?? [];

        foreach ($extensions as $ext) {
            if ($ext['extnId'] !== 'id-pe-authorityInfoAccess') {
                continue;
            }

            foreach ($ext['extnValue'] as $accessDesc) {
                if ($accessDesc['accessMethod'] === 'id-ad-ocsp') {
                    return $accessDesc['accessLocation']['uniformResourceIdentifier'] ?? '';
                }
            }
        }

        return '';
    }

    /**
     * Send the OCSP request via HTTP POST and return the raw DER response.
     *
     * @throws ValidationException
     */
    private function sendOcspRequest(string $url, string $derRequestBody): string
    {
        try {
            $response = $this->httpClient->post($url, [
                'headers' => [
                    'Content-Type' => self::OCSP_REQUEST_CONTENT_TYPE,
                ],
                'body' => $derRequestBody,
                'timeout' => $this->timeoutSeconds,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                throw new ValidationException(
                    sprintf('OCSP responder returned HTTP status %d', $statusCode),
                );
            }

            $contentType = $response->getHeaderLine('Content-Type');
            if (!str_contains($contentType, self::OCSP_RESPONSE_CONTENT_TYPE)) {
                throw new ValidationException(
                    sprintf('OCSP responder returned unexpected Content-Type: %s', $contentType),
                );
            }

            return $response->getBody()->getContents();
        } catch (GuzzleException $e) {
            throw new ValidationException(
                sprintf('OCSP request failed: %s', $e->getMessage()),
            );
        }
    }
}
