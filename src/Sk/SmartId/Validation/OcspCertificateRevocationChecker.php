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
use phpseclib3\File\ASN1;
use phpseclib3\File\ASN1\Maps\Certificate;
use phpseclib3\File\ASN1\Maps\Name;
use phpseclib3\File\X509;
use phpseclib3\Math\BigInteger;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sk\SmartId\Exception\ValidationException;
use Sk\SmartId\Util\DerNavigator;
use Sk\SmartId\Validation\Maps\OcspBasicResponseMap;
use Sk\SmartId\Validation\Maps\OcspResponseMap;

class OcspCertificateRevocationChecker
{
    private const OCSP_REQUEST_CONTENT_TYPE = 'application/ocsp-request';

    private const OCSP_RESPONSE_CONTENT_TYPE = 'application/ocsp-response';

    private const DEFAULT_MAX_RESPONSE_AGE_SECONDS = 900;

    private const OCSP_NONCE_EXTENSION_OID_DER = "\x2B\x06\x01\x05\x05\x07\x30\x01\x02";

    private const NONCE_LENGTH = 32;

    private const ARCHIVE_CUTOFF_OID = '1.3.6.1.5.5.7.48.1.6';

    private const OCSP_NONCE_OID = '1.3.6.1.5.5.7.48.1.2';

    private const OCSP_SIGNING_EKU_OID = '1.3.6.1.5.5.7.3.9';

    /** @var int Clock skew tolerance in seconds for time validation */
    private const CLOCK_SKEW_TOLERANCE_SECONDS = 120;

    /** @var string SHA-1 OID in DER-encoded form (used for OCSP CertID hash algorithm) */
    private const SHA1_OID_DER = "\x2B\x0E\x03\x02\x1A";

    private ClientInterface $httpClient;

    private RequestFactoryInterface $requestFactory;

    private StreamFactoryInterface $streamFactory;

    private LoggerInterface $logger;

    // used only for demo mock OCSP server
    private ?string $ocspUrlOverride;

    private ?string $designatedResponderCertPem;

    private int $maxResponseAgeSeconds;

    private bool $useNonce;

    private bool $requireEmbeddedResponderCert;

    /** @internal For testing only */
    private ?string $nonceForTesting = null;

    public function __construct(
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        ?string $ocspUrlOverride = null,
        ?string $designatedResponderCertPem = null,
        int $maxResponseAgeSeconds = self::DEFAULT_MAX_RESPONSE_AGE_SECONDS,
        bool $useNonce = true,
        bool $requireEmbeddedResponderCert = true,
        ?LoggerInterface $logger = null,
    ) {
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
        $this->ocspUrlOverride = $ocspUrlOverride;
        $this->designatedResponderCertPem = $designatedResponderCertPem;
        $this->maxResponseAgeSeconds = $maxResponseAgeSeconds;
        $this->useNonce = $useNonce;
        $this->requireEmbeddedResponderCert = $requireEmbeddedResponderCert;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Create a checker that uses the AIA OCSP URL from the certificate
     * and validates the responder certificate against the issuer CA.
     */
    public static function create(?LoggerInterface $logger = null): self
    {
        $factory = new HttpFactory();

        return new self(new Client(), $factory, $factory, logger: $logger);
    }

    /**
     * Create a checker with a designated OCSP responder URL and pinned responder certificate.
     * The responder certificate from the OCSP response must match the provided certificate exactly.
     */
    public static function createDesignated(string $ocspUrl, string $responderCertPem, ?LoggerInterface $logger = null): self
    {
        $factory = new HttpFactory();

        return new self(new Client(), $factory, $factory, $ocspUrl, $responderCertPem, logger: $logger);
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

            $issuerNameHash = sha1($issuerNameDer, true);
            $issuerKeyHash = sha1($issuerPublicKeyBytes, true);

            $nonce = $this->useNonce ? ($this->nonceForTesting ?? random_bytes(self::NONCE_LENGTH)) : null;
            $requestBody = $this->buildOcspRequest($issuerNameHash, $issuerKeyHash, $serialNumber, $nonce);

            $this->logger->debug('Sending OCSP request', ['url' => $ocspResponderUrl]);
            $responseBody = $this->sendOcspRequest($ocspResponderUrl, $requestBody);
            $this->logger->debug('OCSP response received', ['url' => $ocspResponderUrl]);

            $this->parseOcspResponse($responseBody, $issuerCertPem, $issuerNameHash, $issuerKeyHash, $serialNumber, $nonce);
            $this->logger->debug('OCSP revocation check passed');
        } catch (ValidationException $e) {
            $this->logger->warning('OCSP revocation check failed', ['error' => $e->getMessage()]);
            throw $e;
        } catch (\Exception $e) {
            $this->logger->warning('OCSP check failed', ['error' => $e->getMessage()]);
            throw new ValidationException(
                sprintf('OCSP check failed: %s', $e->getMessage()),
            );
        }
    }

    private function buildOcspRequest(string $issuerNameHash, string $issuerKeyHash, BigInteger $serialNumber, ?string $nonce = null): string
    {
        $serialBytes = $serialNumber->toBytes(true);

        $hashAlgorithm = $this->derSequence(
            $this->derOid(self::SHA1_OID_DER),
        );

        $certId = $this->derSequence(
            $hashAlgorithm
            . $this->derOctetString($issuerNameHash)
            . $this->derOctetString($issuerKeyHash)
            . $this->derInteger($serialBytes),
        );

        $request = $this->derSequence($certId);

        $requestList = $this->derSequence($request);

        if ($nonce !== null) {
            $nonceOid = $this->derOid(self::OCSP_NONCE_EXTENSION_OID_DER);
            $nonceExtnValue = $this->derOctetString($this->derOctetString($nonce));
            $nonceExtension = $this->derSequence($nonceOid . $nonceExtnValue);
            $extensions = $this->derSequence($nonceExtension);
            $requestExtensions = "\xA2" . $this->derLength($extensions) . $extensions;
            $tbsRequest = $this->derSequence($requestList . $requestExtensions);
        } else {
            $tbsRequest = $this->derSequence($requestList);
        }

        return $this->derSequence($tbsRequest);
    }

    private function parseOcspResponse(string $responseBody, string $issuerCertPem, string $expectedIssuerNameHash, string $expectedIssuerKeyHash, BigInteger $expectedSerialNumber, ?string $expectedNonce = null): void
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

        $this->verifyResponseVersion($basicResponse);
        $this->verifyCertStatus($basicResponse, $expectedIssuerNameHash, $expectedIssuerKeyHash, $expectedSerialNumber);
        $this->verifyResponseFreshness($basicResponse);
        $this->verifyArchiveCutoff($basicResponse);

        if ($expectedNonce !== null) {
            $this->verifyNonce($basicResponse, $expectedNonce);
        }

        if ($basicResponseRawDer === null) {
            throw new ValidationException('Failed to extract raw BasicOCSPResponse DER');
        }
        $this->verifyResponseSignature($basicResponse, $basicResponseRawDer, $issuerCertPem);
    }

    private function verifyCertStatus(array $basicResponse, string $expectedIssuerNameHash, string $expectedIssuerKeyHash, BigInteger $expectedSerialNumber): void
    {
        $responses = $basicResponse['tbsResponseData']['responses'] ?? [];
        if (count($responses) !== 1) {
            throw new ValidationException(
                sprintf('OCSP response must contain exactly one SingleResponse, got %d', count($responses)),
            );
        }

        $this->verifyCertId($responses[0], $expectedIssuerNameHash, $expectedIssuerKeyHash, $expectedSerialNumber);

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

    private function verifyCertId(
        array $singleResponse,
        string $expectedIssuerNameHash,
        string $expectedIssuerKeyHash,
        BigInteger $expectedSerialNumber,
    ): void {
        $certId = $singleResponse['certID'] ?? null;
        if (!is_array($certId)) {
            throw new ValidationException('OCSP SingleResponse is missing certID');
        }

        $responseIssuerNameHash = $certId['issuerNameHash'] ?? null;
        $responseIssuerKeyHash = $certId['issuerKeyHash'] ?? null;
        $responseSerialNumber = $certId['serialNumber'] ?? null;

        if ($responseIssuerNameHash === null || $responseIssuerKeyHash === null || $responseSerialNumber === null) {
            throw new ValidationException('OCSP response certID is missing required fields');
        }

        if (!hash_equals($expectedIssuerNameHash, $responseIssuerNameHash)) {
            throw new ValidationException(
                'OCSP response certID issuerNameHash does not match the requested certificate',
            );
        }

        if (!hash_equals($expectedIssuerKeyHash, $responseIssuerKeyHash)) {
            throw new ValidationException(
                'OCSP response certID issuerKeyHash does not match the requested certificate',
            );
        }

        if (!$expectedSerialNumber->equals($responseSerialNumber)) {
            throw new ValidationException(
                'OCSP response certID serialNumber does not match the requested certificate',
            );
        }
    }

    private function verifyResponseVersion(array $basicResponse): void
    {
        $version = $basicResponse['tbsResponseData']['version'] ?? 'v1';
        if ($version !== 'v1') {
            throw new ValidationException(
                sprintf('OCSP response version must be v1, got: %s', (string) $version),
            );
        }
    }

    private function verifyResponseFreshness(array $basicResponse): void
    {
        $responses = $basicResponse['tbsResponseData']['responses'] ?? [];
        if (count($responses) === 0) {
            return;
        }

        $thisUpdateRaw = $responses[0]['thisUpdate'] ?? null;
        if ($thisUpdateRaw === null) {
            throw new ValidationException('OCSP response SingleResponse is missing thisUpdate');
        }

        $thisUpdate = $this->parseGeneralizedTime($thisUpdateRaw);
        $now = time();

        // thisUpdate must not be in the future (with clock skew tolerance)
        if ($thisUpdate > $now + self::CLOCK_SKEW_TOLERANCE_SECONDS) {
            throw new ValidationException('OCSP response thisUpdate is in the future');
        }

        // thisUpdate must not be too old
        if ($this->maxResponseAgeSeconds > 0) {
            $age = $now - $thisUpdate;
            if ($age > $this->maxResponseAgeSeconds) {
                throw new ValidationException(
                    sprintf(
                        'OCSP response is too old: thisUpdate was %d seconds ago (max allowed: %d)',
                        $age,
                        $this->maxResponseAgeSeconds,
                    ),
                );
            }
        }

        // If nextUpdate is present, current time must be before nextUpdate
        $nextUpdateRaw = $responses[0]['nextUpdate'] ?? null;
        if ($nextUpdateRaw !== null) {
            $nextUpdate = $this->parseGeneralizedTime($nextUpdateRaw);
            if ($now > $nextUpdate) {
                throw new ValidationException('OCSP response has expired: current time is past nextUpdate');
            }
        }

        // Verify producedAt is present (mandatory per SK profile) and not in the future
        $producedAtRaw = $basicResponse['tbsResponseData']['producedAt'] ?? null;
        if ($producedAtRaw === null) {
            throw new ValidationException('OCSP response is missing mandatory producedAt field');
        }

        $producedAt = $this->parseGeneralizedTime($producedAtRaw);
        if ($producedAt > $now + self::CLOCK_SKEW_TOLERANCE_SECONDS) {
            throw new ValidationException('OCSP response producedAt is in the future');
        }
    }

    private function verifyArchiveCutoff(array $basicResponse): void
    {
        $responses = $basicResponse['tbsResponseData']['responses'] ?? [];
        if (count($responses) === 0) {
            return;
        }

        $singleExtensions = $responses[0]['singleExtensions'] ?? [];

        foreach ($singleExtensions as $ext) {
            $extId = $ext['extnId'] ?? '';
            if ($extId === 'id-pkix-ocsp-archive-cutoff' || $extId === self::ARCHIVE_CUTOFF_OID) {
                return;
            }
        }

        throw new ValidationException('OCSP response SingleResponse is missing mandatory Archive Cutoff extension');
    }

    private function verifyNonce(array $basicResponse, string $expectedNonce): void
    {
        $responseExtensions = $basicResponse['tbsResponseData']['responseExtensions'] ?? [];

        foreach ($responseExtensions as $ext) {
            $extId = $ext['extnId'] ?? '';
            if ($extId === 'id-pkix-ocsp-nonce' || $extId === self::OCSP_NONCE_OID) {
                $extnValue = $ext['extnValue'] ?? '';

                // extnValue is an OCTET STRING containing DER-encoded OCTET STRING
                $decoded = ASN1::decodeBER($extnValue);
                if (is_array($decoded) && isset($decoded[0]['content'])) {
                    $responseNonce = $decoded[0]['content'];
                } else {
                    $responseNonce = $extnValue;
                }

                if (!hash_equals($expectedNonce, $responseNonce)) {
                    throw new ValidationException('OCSP response nonce does not match the request nonce');
                }

                return;
            }
        }

        throw new ValidationException('OCSP response does not contain a nonce extension');
    }

    /**
     * @param string|\DateTimeInterface $value
     */
    private function parseGeneralizedTime(string|\DateTimeInterface $value): int
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->getTimestamp();
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            throw new ValidationException(
                sprintf('Failed to parse OCSP response time value: %s', $value),
            );
        }

        return $timestamp;
    }

    /** @internal For testing only */
    public function setNonceForTesting(string $nonce): void
    {
        $this->nonceForTesting = $nonce;
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
        // tbsResponseData is the first child of the outer BasicOCSPResponse SEQUENCE.
        // We extract the original bytes to verify the signature against the exact encoding.
        $tbsNode = DerNavigator::fromDer($basicResponseRawDer, 'BasicOCSPResponse')
            ->child(0, 'tbsResponseData');

        return $tbsNode->extractRawBytes($basicResponseRawDer);
    }

    private function resolveResponderCertificate(array $basicResponse, string $issuerCertPem): string
    {
        $embeddedCerts = $basicResponse['certs'] ?? [];

        if (count($embeddedCerts) > 0) {
            // Use the first embedded certificate as the responder certificate
            $responderCertDer = ASN1::encodeDER($embeddedCerts[0], Certificate::MAP);
            $responderCertPem = "-----BEGIN CERTIFICATE-----\n"
                . chunk_split(base64_encode($responderCertDer), 64)
                . '-----END CERTIFICATE-----';

            if ($this->designatedResponderCertPem !== null) {
                $this->validateDesignatedResponderCertificate($responderCertPem);
            } else {
                $this->validateResponderCertificate($responderCertPem, $issuerCertPem);
            }

            return $responderCertPem;
        }

        // No embedded certs — SK OCSP Profile mandates the responder certificate is included.
        if ($this->requireEmbeddedResponderCert) {
            throw new ValidationException(
                'OCSP response does not contain an embedded responder certificate (required by SK OCSP Profile)',
            );
        }

        // Fallback: the issuer CA itself is the responder ("Authorized Responder" per RFC 6960 §2.2)
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
        $hasOcspSigning = str_contains($extKeyUsage, self::OCSP_SIGNING_EKU_OID)
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
        // Per SK Smart-ID OCSP Profile, only sha256WithRSAEncryption and
        // sha512WithRSAEncryption are allowed signature algorithms.
        return match (true) {
            str_contains($algorithm, 'sha256') && str_contains($algorithm, 'rsa') => OPENSSL_ALGO_SHA256,
            str_contains($algorithm, 'sha512') && str_contains($algorithm, 'rsa') => OPENSSL_ALGO_SHA512,
            default => throw new ValidationException(
                sprintf('Unsupported OCSP response signature algorithm: %s (only sha256WithRSAEncryption and sha512WithRSAEncryption are allowed)', $algorithm),
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

        return DerNavigator::extractPublicKeyBytesFromCertDer($der);
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
