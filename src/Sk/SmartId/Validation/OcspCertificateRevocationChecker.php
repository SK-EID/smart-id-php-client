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

class OcspCertificateRevocationChecker
{
    private const OCSP_REQUEST_CONTENT_TYPE = 'application/ocsp-request';

    private const OCSP_RESPONSE_CONTENT_TYPE = 'application/ocsp-response';

    private Client $httpClient;

    private int $timeoutSeconds;

    private ?string $ocspUrlOverride;

    public function __construct(?Client $httpClient = null, int $timeoutSeconds = 10, ?string $ocspUrlOverride = null)
    {
        $this->httpClient = $httpClient ?? new Client();
        $this->timeoutSeconds = $timeoutSeconds;
        $this->ocspUrlOverride = $ocspUrlOverride;
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

            $this->parseOcspResponse($responseBody);
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

    private function parseOcspResponse(string $responseBody): void
    {
        $decoded = ASN1::decodeBER($responseBody);
        if ($decoded === null || !isset($decoded[0]['content'])) {
            throw new ValidationException('Failed to decode OCSP response');
        }

        $ocspResponse = $decoded[0]['content'];

        $statusValue = $ocspResponse[0]['content'] ?? null;
        if ($statusValue === null) {
            throw new ValidationException('OCSP response missing responseStatus');
        }
        $statusCode = $this->normalizeToInt($statusValue);
        if ($statusCode !== 0) {
            $this->throwForOcspStatus($statusCode);
        }

        $responseBytes = $ocspResponse[1] ?? null;
        if ($responseBytes === null || !isset($responseBytes['content'])) {
            throw new ValidationException('OCSP response missing responseBytes');
        }

        $innerSeq = $responseBytes['content'][0] ?? $responseBytes;
        $basicResponseDer = null;
        foreach (($innerSeq['content'] ?? []) as $el) {
            if (($el['type'] ?? 0) === 4) {
                $basicResponseDer = $el['content'];
                break;
            }
        }

        if ($basicResponseDer === null) {
            throw new ValidationException('OCSP response missing BasicOCSPResponse');
        }

        $basicDecoded = ASN1::decodeBER($basicResponseDer);
        if ($basicDecoded === null || !isset($basicDecoded[0]['content'][0]['content'])) {
            throw new ValidationException('Failed to decode BasicOCSPResponse');
        }

        $tbsResponseData = $basicDecoded[0]['content'][0]['content'];
        $responsesSeq = null;
        foreach ($tbsResponseData as $child) {
            if (($child['type'] ?? 0) === 16 && ($child['class'] ?? 0) === 0) {
                $responsesSeq = $child;
            }
        }

        if ($responsesSeq === null || !isset($responsesSeq['content'][0]['content'])) {
            throw new ValidationException('OCSP response missing SingleResponse');
        }

        $singleResponse = $responsesSeq['content'][0]['content'];
        if (count($singleResponse) < 2) {
            throw new ValidationException('OCSP SingleResponse is malformed');
        }

        $certStatus = $singleResponse[1];
        $tag = $certStatus['constant'] ?? $certStatus['type'] ?? -1;
        match ($tag) {
            0 => null,
            1 => throw new ValidationException('Certificate has been revoked'),
            2 => throw new ValidationException('Certificate revocation status is unknown'),
            default => throw new ValidationException(
                sprintf('Unexpected OCSP certStatus tag: %d', $tag),
            ),
        };
    }

    private function normalizeToInt(mixed $value): int
    {
        if ($value instanceof BigInteger) {
            return (int) $value->toString();
        }
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value)) {
            return ord($value);
        }

        return -1;
    }

    private function throwForOcspStatus(int $code): void
    {
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
