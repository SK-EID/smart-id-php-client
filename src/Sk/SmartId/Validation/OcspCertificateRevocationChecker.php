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
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use phpseclib3\File\ASN1;
use phpseclib3\File\ASN1\Maps\Certificate;
use phpseclib3\File\ASN1\Maps\Name;
use phpseclib3\File\X509;
use phpseclib3\Math\BigInteger;
use Sk\SmartId\Exception\ValidationException;
use Sk\SmartId\Validation\Maps\OcspBasicResponseMap;
use Sk\SmartId\Validation\Maps\OcspResponseMap;

class OcspCertificateRevocationChecker
{
    private const OCSP_REQUEST_CONTENT_TYPE = 'application/ocsp-request';

    private const OCSP_RESPONSE_CONTENT_TYPE = 'application/ocsp-response';

    private ClientInterface $httpClient;

    private RequestFactoryInterface $requestFactory;

    private StreamFactoryInterface $streamFactory;

    private ?string $ocspUrlOverride;

    private ?string $designatedResponderCertPem;

    public function __construct(
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        ?string $ocspUrlOverride = null,
        ?string $designatedResponderCertPem = null,
    ) {
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
        $this->ocspUrlOverride = $ocspUrlOverride;
        $this->designatedResponderCertPem = $designatedResponderCertPem;
    }

    /**
     * Create a checker that uses the AIA OCSP URL from the certificate
     * and validates the responder certificate against the issuer CA.
     */
    public static function create(): self
    {
        $factory = new HttpFactory();

        return new self(new Client(), $factory, $factory);
    }

    /**
     * Create a checker with a designated OCSP responder URL and pinned responder certificate.
     * The responder certificate from the OCSP response must match the provided certificate exactly.
     */
    public static function createDesignated(string $ocspUrl, string $responderCertPem): self
    {
        $factory = new HttpFactory();

        return new self(new Client(), $factory, $factory, $ocspUrl, $responderCertPem);
    }

    public function checkRevocationStatus(string $subjectCertPem, string $issuerCertPem): void
    {
        try {
            $subjectCert = $this->parseCertificate($subjectCertPem, 'subject');

            $ocspResponderUrl = $this->ocspUrlOverride ?? $this->extractOcspUrl($subjectCert);
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

            $this->parseOcspResponse($responseBody, $issuerCertPem);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new ValidationException(
                sprintf('OCSP check failed: %s', $e->getMessage()),
            );
        }
    }

    private function buildOcspRequest(string $issuerNameDer, string $issuerPublicKeyBytes, BigInteger $serialNumber): string
    {
        $issuerNameHash = sha1($issuerNameDer, true);
        $issuerKeyHash = sha1($issuerPublicKeyBytes, true);
        $serialBytes = $serialNumber->toBytes(true);

        $hashAlgorithm = $this->derSequence(
            $this->derOid("\x2B\x0E\x03\x02\x1A"),
        );

        $certId = $this->derSequence(
            $hashAlgorithm
            . $this->derOctetString($issuerNameHash)
            . $this->derOctetString($issuerKeyHash)
            . $this->derInteger($serialBytes),
        );

        $request = $this->derSequence($certId);

        $requestList = $this->derSequence($request);

        $tbsRequest = $this->derSequence($requestList);

        return $this->derSequence($tbsRequest);
    }

    private function parseOcspResponse(string $responseBody, string $issuerCertPem): void
    {
        $decoded = ASN1::decodeBER($responseBody);
        if (!is_array($decoded) || !isset($decoded[0])) {
            throw new ValidationException('Failed to decode OCSP response');
        }

        // Capture the raw BasicOCSPResponse DER for signature verification.
        // ASN1::encodeDER does not always produce byte-identical output to the
        // original, so we must verify the signature against the original bytes.
        $basicResponseRawDer = null;

        $ocspResponse = ASN1::asn1map($decoded[0], OcspResponseMap::MAP, [
            'response' => function (string $encoded) use (&$basicResponseRawDer): array {
                $basicResponseRawDer = $encoded;

                $inner = ASN1::decodeBER($encoded);
                if (!is_array($inner) || !isset($inner[0])) {
                    throw new ValidationException('Failed to decode BasicOCSPResponse');
                }

                return ASN1::asn1map($inner[0], OcspBasicResponseMap::MAP);
            },
        ]);

        if ($ocspResponse === null) {
            throw new ValidationException('Failed to decode OCSP response');
        }

        $responseStatus = $ocspResponse['responseStatus'] ?? null;
        if ($responseStatus !== 'successful') {
            $this->throwForOcspResponseStatus($responseStatus);
        }

        if (!isset($ocspResponse['responseBytes']['response'])) {
            throw new ValidationException('OCSP response missing responseBytes');
        }

        $basicResponse = $ocspResponse['responseBytes']['response'];
        if (!is_array($basicResponse)) {
            throw new ValidationException('Failed to decode BasicOCSPResponse');
        }

        $this->verifyCertStatus($basicResponse);
        $this->verifyResponseSignature($basicResponse, $basicResponseRawDer, $issuerCertPem);
    }

    private function verifyCertStatus(array $basicResponse): void
    {
        $responses = $basicResponse['tbsResponseData']['responses'] ?? [];
        if (count($responses) !== 1) {
            throw new ValidationException(
                sprintf('OCSP response must contain exactly one SingleResponse, got %d', count($responses)),
            );
        }

        $certStatus = $responses[0]['certStatus'] ?? null;
        if ($certStatus === null) {
            throw new ValidationException('OCSP SingleResponse is missing certStatus');
        }

        if (isset($certStatus['good'])) {
            return;
        }

        if (isset($certStatus['revoked'])) {
            throw new ValidationException('Certificate has been revoked');
        }

        if (isset($certStatus['unknown'])) {
            throw new ValidationException('Certificate revocation status is unknown');
        }

        throw new ValidationException('Unexpected OCSP certStatus');
    }

    private function verifyResponseSignature(array $basicResponse, string $basicResponseRawDer, string $issuerCertPem): void
    {
        $responderCertPem = $this->resolveResponderCertificate($basicResponse, $issuerCertPem);

        $signatureAlgorithm = strtolower($basicResponse['signatureAlgorithm']['algorithm'] ?? '');
        $opensslAlgo = $this->mapSignatureAlgorithm($signatureAlgorithm);

        $signatureRaw = $basicResponse['signature'] ?? null;
        if ($signatureRaw === null || $signatureRaw === '') {
            throw new ValidationException('OCSP BasicOCSPResponse is missing signature');
        }
        // BIT STRING has a leading unused-bits byte that must be stripped
        $signature = substr($signatureRaw, 1);

        // Extract the original tbsResponseData DER bytes directly from the raw
        // BasicOCSPResponse. We must NOT re-encode via ASN1::encodeDER because
        // it may produce bytes that differ from the original signed content.
        $tbsResponseDataDer = $this->extractTbsResponseDataDer($basicResponseRawDer);

        $responderKey = openssl_pkey_get_public($responderCertPem);
        if ($responderKey === false) {
            throw new ValidationException('Failed to extract public key from OCSP responder certificate');
        }

        $result = openssl_verify($tbsResponseDataDer, $signature, $responderKey, $opensslAlgo);
        if ($result !== 1) {
            throw new ValidationException('OCSP response signature verification failed');
        }
    }

    private function extractTbsResponseDataDer(string $basicResponseRawDer): string
    {
        // Decode the raw BasicOCSPResponse to locate the tbsResponseData node.
        // tbsResponseData is the first child of the outer SEQUENCE.
        $decoded = ASN1::decodeBER($basicResponseRawDer);
        if (!is_array($decoded) || !isset($decoded[0]['content'][0])) {
            throw new ValidationException('Failed to locate tbsResponseData in BasicOCSPResponse');
        }

        $tbsNode = $decoded[0]['content'][0];

        // In phpseclib's decodeBER, for child nodes: 'start' is the byte offset
        // within the input and 'length' is the total occupied bytes (header + content).
        return substr($basicResponseRawDer, $tbsNode['start'], $tbsNode['length']);
    }

    private function resolveResponderCertificate(array $basicResponse, string $issuerCertPem): string
    {
        $embeddedCerts = $basicResponse['certs'] ?? [];

        if (count($embeddedCerts) > 0) {
            // Use the first embedded certificate as the responder certificate
            $responderCertDer = ASN1::encodeDER($embeddedCerts[0], Certificate::MAP);
            $responderCertPem = "-----BEGIN CERTIFICATE-----\n"
                . chunk_split(base64_encode($responderCertDer), 64)
                . "-----END CERTIFICATE-----";

            if ($this->designatedResponderCertPem !== null) {
                $this->validateDesignatedResponderCertificate($responderCertPem);
            } else {
                $this->validateResponderCertificate($responderCertPem, $issuerCertPem);
            }

            return $responderCertPem;
        }

        // No embedded certs — the issuer CA itself must be the responder ("Authorized Responder" per RFC 6960 §2.2)
        return $issuerCertPem;
    }

    private function validateDesignatedResponderCertificate(string $responderCertPem): void
    {
        // Certificate pinning: the responder cert from the OCSP response must match
        // the pre-configured designated responder certificate exactly.
        $actualFingerprint = openssl_x509_fingerprint($responderCertPem, 'sha256');
        $expectedFingerprint = openssl_x509_fingerprint($this->designatedResponderCertPem, 'sha256');

        if ($actualFingerprint === false || $expectedFingerprint === false) {
            throw new ValidationException('Failed to compute OCSP responder certificate fingerprint');
        }

        if (!hash_equals($expectedFingerprint, $actualFingerprint)) {
            throw new ValidationException(
                'OCSP responder certificate does not match the designated responder certificate',
            );
        }

        // Verify the designated responder certificate is not expired
        $certInfo = openssl_x509_parse($responderCertPem);
        if ($certInfo === false) {
            throw new ValidationException('Failed to parse OCSP responder certificate');
        }

        $now = time();
        if (isset($certInfo['validFrom_time_t']) && $certInfo['validFrom_time_t'] > $now) {
            throw new ValidationException('OCSP responder certificate is not yet valid');
        }
        if (isset($certInfo['validTo_time_t']) && $certInfo['validTo_time_t'] < $now) {
            throw new ValidationException('OCSP responder certificate has expired');
        }
    }

    private function validateResponderCertificate(string $responderCertPem, string $issuerCertPem): void
    {
        // Verify that the responder certificate is signed by the issuer CA
        $verifyResult = openssl_x509_verify($responderCertPem, $issuerCertPem);
        if ($verifyResult !== 1) {
            throw new ValidationException(
                'OCSP responder certificate is not signed by the expected issuer CA',
            );
        }

        // Verify the responder certificate has id-kp-OCSPSigning EKU (OID 1.3.6.1.5.5.7.3.9)
        $certInfo = openssl_x509_parse($responderCertPem);
        if ($certInfo === false) {
            throw new ValidationException('Failed to parse OCSP responder certificate');
        }

        $extKeyUsage = $certInfo['extensions']['extendedKeyUsage'] ?? '';
        $hasOcspSigning = str_contains($extKeyUsage, '1.3.6.1.5.5.7.3.9')
            || stripos($extKeyUsage, 'OCSP Signing') !== false;

        if (!$hasOcspSigning) {
            throw new ValidationException(
                'OCSP responder certificate does not have id-kp-OCSPSigning Extended Key Usage',
            );
        }

        // Verify the responder certificate is not expired
        $now = time();
        if (isset($certInfo['validFrom_time_t']) && $certInfo['validFrom_time_t'] > $now) {
            throw new ValidationException('OCSP responder certificate is not yet valid');
        }
        if (isset($certInfo['validTo_time_t']) && $certInfo['validTo_time_t'] < $now) {
            throw new ValidationException('OCSP responder certificate has expired');
        }
    }

    private function mapSignatureAlgorithm(string $algorithm): int
    {
        // Map OID names from phpseclib ASN1 decoding to OpenSSL algorithm constants
        return match (true) {
            str_contains($algorithm, 'sha512') && str_contains($algorithm, 'rsa') => OPENSSL_ALGO_SHA512,
            str_contains($algorithm, 'sha384') && str_contains($algorithm, 'rsa') => OPENSSL_ALGO_SHA384,
            str_contains($algorithm, 'sha256') && str_contains($algorithm, 'rsa') => OPENSSL_ALGO_SHA256,
            str_contains($algorithm, 'sha1') && str_contains($algorithm, 'rsa') => OPENSSL_ALGO_SHA1,
            str_contains($algorithm, 'sha512') => OPENSSL_ALGO_SHA512,
            str_contains($algorithm, 'sha384') => OPENSSL_ALGO_SHA384,
            str_contains($algorithm, 'sha256') => OPENSSL_ALGO_SHA256,
            str_contains($algorithm, 'sha1') => OPENSSL_ALGO_SHA1,
            default => throw new ValidationException(
                sprintf('Unsupported OCSP response signature algorithm: %s', $algorithm),
            ),
        };
    }

    private function throwForOcspResponseStatus(?string $status): never
    {
        $message = match ($status) {
            'malformedRequest' => 'OCSP responder: malformed request',
            'internalError' => 'OCSP responder: internal error',
            'tryLater' => 'OCSP responder: try later',
            'sigRequired' => 'OCSP responder: signature required',
            'unauthorized' => 'OCSP responder: unauthorized',
            null => 'OCSP response missing responseStatus',
            default => sprintf('OCSP responder returned error status: %s', $status),
        };
        throw new ValidationException($message);
    }

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

    /**
     * @return array<string, mixed>
     */
    private function parseCertificate(string $pem, string $label): array
    {
        $x509 = new X509();
        $cert = $x509->loadX509($pem);

        if ($cert === false || !isset($cert['tbsCertificate'])) {
            throw new ValidationException(
                sprintf('Failed to parse %s certificate', $label),
            );
        }

        return $cert;
    }

    private function extractRawPublicKeyBytes(string $pem): string
    {
        $certResource = openssl_x509_read($pem);
        if ($certResource === false) {
            throw new ValidationException('Failed to read issuer certificate');
        }

        if (!openssl_x509_export($certResource, $cleanPem)) {
            throw new ValidationException('Failed to export issuer certificate');
        }

        $base64 = preg_replace('/-----[A-Z ]+-----/', '', $cleanPem);
        $der = base64_decode(str_replace(["\r", "\n", ' '], '', $base64 ?? ''), true);

        if ($der === false || $der === '') {
            throw new ValidationException('Failed to decode issuer certificate DER');
        }

        $decoded = ASN1::decodeBER($der);
        if ($decoded === null || !isset($decoded[0]['content'][0]['content'][6]['content'][1]['content'])) {
            throw new ValidationException('Failed to extract public key from issuer certificate');
        }

        $bitStringContent = $decoded[0]['content'][0]['content'][6]['content'][1]['content'];

        return substr($bitStringContent, 1);
    }

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

    private function sendOcspRequest(string $url, string $derRequestBody): string
    {
        try {
            $request = $this->requestFactory->createRequest('POST', $url)
                ->withHeader('Content-Type', self::OCSP_REQUEST_CONTENT_TYPE)
                ->withBody($this->streamFactory->createStream($derRequestBody));

            $response = $this->httpClient->sendRequest($request);

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
        } catch (ClientExceptionInterface $e) {
            throw new ValidationException(
                sprintf('OCSP request failed: %s', $e->getMessage()),
            );
        }
    }
}
