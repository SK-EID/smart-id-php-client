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

use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\RSA\PublicKey as RSAPublicKey;
use Sk\SmartId\Enum\CertificateLevel;
use Sk\SmartId\Exception\SmartIdException;
use Sk\SmartId\Exception\ValidationException;
use Sk\SmartId\Model\AuthenticationIdentity;
use Sk\SmartId\Session\SessionCertificate;
use Sk\SmartId\Session\SessionSignature;
use Sk\SmartId\Session\SessionStatus;

class AuthenticationResponseValidator
{
    /** @var string[] */
    private array $trustedCaCertificates = [];

    private bool $skipSignatureVerification = false;

    /**
     * @return string[]
     */
    public function getTrustedCaCertificates(): array
    {
        return $this->trustedCaCertificates;
    }

    /**
     * @param string[] $certificates Array of PEM-encoded CA certificates
     */
    public function setTrustedCaCertificates(array $certificates): self
    {
        $this->trustedCaCertificates = $certificates;

        return $this;
    }

    public function addTrustedCaCertificate(string $certificate): self
    {
        $this->trustedCaCertificates[] = $certificate;

        return $this;
    }

    /**
     * Skip signature verification.
     * WARNING: Only use this if you understand the security implications.
     * Certificate trust verification still provides authentication.
     */
    public function setSkipSignatureVerification(bool $skip = true): self
    {
        $this->skipSignatureVerification = $skip;

        return $this;
    }

    /**
     * @throws SmartIdException
     * @throws ValidationException
     */
    public function validate(
        SessionStatus $sessionStatus,
        string $rpChallenge,
        ?CertificateLevel $requiredCertificateLevel = null,
    ): AuthenticationIdentity {
        if (!$sessionStatus->isComplete()) {
            throw new ValidationException('Cannot validate incomplete session');
        }

        $result = $sessionStatus->getResult();
        if ($result === null || !$result->isOk()) {
            throw new ValidationException('Session result is not OK');
        }

        $cert = $sessionStatus->getCert();
        if ($cert === null) {
            throw new ValidationException('Certificate is missing from session response');
        }

        $signature = $sessionStatus->getSignature();
        if ($signature === null) {
            throw new ValidationException('Signature is missing from session response');
        }

        if ($requiredCertificateLevel !== null) {
            $this->verifyCertificateLevel($cert, $requiredCertificateLevel);
        }

        $this->verifyCertificateTrust($cert);

        if (!$this->skipSignatureVerification) {
            $this->verifySignature($sessionStatus, $rpChallenge);
        }

        return $this->extractIdentity($cert);
    }

    /**
     * @throws ValidationException
     */
    private function verifyCertificateLevel(SessionCertificate $cert, CertificateLevel $required): void
    {
        $actualLevel = CertificateLevel::tryFromString($cert->getCertificateLevel());

        if ($actualLevel === null) {
            throw new ValidationException(
                sprintf('Unknown certificate level: %s', $cert->getCertificateLevel()),
            );
        }

        if (!$actualLevel->meetsRequirement($required)) {
            throw new ValidationException(
                sprintf(
                    'Certificate level %s does not meet required level %s',
                    $actualLevel->value,
                    $required->value,
                ),
            );
        }
    }

    /**
     * @throws ValidationException
     */
    private function verifyCertificateTrust(SessionCertificate $cert): void
    {
        if (empty($this->trustedCaCertificates)) {
            throw new ValidationException('No trusted CA certificates configured');
        }

        $certPem = $cert->getPemEncodedCertificate();
        $certResource = openssl_x509_read($certPem);

        if ($certResource === false) {
            throw new ValidationException('Failed to parse certificate');
        }

        $certInfo = openssl_x509_parse($certResource);
        if ($certInfo === false) {
            throw new ValidationException('Failed to parse certificate information');
        }

        $now = time();
        if (isset($certInfo['validFrom_time_t']) && $certInfo['validFrom_time_t'] > $now) {
            throw new ValidationException('Certificate is not yet valid');
        }

        if (isset($certInfo['validTo_time_t']) && $certInfo['validTo_time_t'] < $now) {
            throw new ValidationException('Certificate has expired');
        }

        $verified = false;
        foreach ($this->trustedCaCertificates as $caCert) {
            if (openssl_x509_verify($certResource, $caCert) === 1) {
                $verified = true;
                break;
            }
        }

        if (!$verified) {
            throw new ValidationException('Certificate is not signed by a trusted CA');
        }
    }

    /**
     * @throws ValidationException
     */
    private function verifySignature(SessionStatus $sessionStatus, string $rpChallenge): void
    {
        $cert = $sessionStatus->getCert();
        $signature = $sessionStatus->getSignature();

        if ($cert === null || $signature === null) {
            throw new ValidationException('Certificate or signature missing');
        }

        $certPem = $cert->getPemEncodedCertificate();
        $publicKey = openssl_pkey_get_public($certPem);

        if ($publicKey === false) {
            throw new ValidationException('Failed to extract public key from certificate');
        }

        $decodedChallenge = base64_decode($rpChallenge, true);
        if ($decodedChallenge === false) {
            throw new ValidationException('Invalid base64 encoded rpChallenge');
        }

        $signatureValue = $signature->getDecodedValue();
        $algorithmName = strtolower($signature->getSignatureAlgorithm());

        if ($algorithmName === 'rsassa-pss') {
            $this->verifyRsaPssSignature($decodedChallenge, $signatureValue, $publicKey, $signature);
        } else {
            $algorithm = $this->mapSignatureAlgorithm($signature->getSignatureAlgorithm());
            $result = openssl_verify($decodedChallenge, $signatureValue, $publicKey, $algorithm);

            if ($result === -1) {
                throw new ValidationException('Signature verification error: ' . openssl_error_string());
            }

            if ($result !== 1) {
                throw new ValidationException('Signature verification failed');
            }
        }
    }

    /**
     * @throws ValidationException
     */
    private function verifyRsaPssSignature(
        string $rpChallenge,
        string $signature,
        \OpenSSLAsymmetricKey $publicKey,
        SessionSignature $sessionSignature,
    ): void {
        $params = $sessionSignature->getSignatureAlgorithmParameters();
        $hashAlgorithm = $params['hashAlgorithm'] ?? 'SHA-512';

        $hashAlgo = match (strtoupper(str_replace('-', '', $hashAlgorithm))) {
            'SHA256' => 'sha256',
            'SHA384' => 'sha384',
            'SHA512' => 'sha512',
            default => throw new ValidationException("Unsupported hash algorithm for RSA-PSS: {$hashAlgorithm}"),
        };

        // For ACSP v2, signature is over: serverRandom || userChallenge || rpChallenge
        $serverRandom = $sessionSignature->getServerRandom();
        $userChallenge = $sessionSignature->getUserChallenge();

        // Validate required fields for ACSP v2
        if ($serverRandom === null || $serverRandom === '') {
            throw new ValidationException('serverRandom is required for RSA-PSS signature verification');
        }
        if ($userChallenge === null || $userChallenge === '') {
            throw new ValidationException('userChallenge is required for RSA-PSS signature verification');
        }

        // Decode URL-safe base64 (convert -_ to +/ and add padding)
        $decodedServerRandom = $this->decodeUrlSafeBase64($serverRandom);
        $decodedUserChallenge = $this->decodeUrlSafeBase64($userChallenge);

        // Construct signed data: serverRandom || userChallenge || rpChallenge
        $signedData = $decodedServerRandom . $decodedUserChallenge . $rpChallenge;

        // Get salt length from parameters (default 64 for SHA-512)
        $saltLength = (int) ($params['saltLength'] ?? 64);

        // Extract public key details
        $keyDetails = openssl_pkey_get_details($publicKey);
        if ($keyDetails === false || !isset($keyDetails['key'])) {
            throw new ValidationException('Failed to extract public key details');
        }

        try {
            // Load public key using phpseclib for RSA-PSS support
            /** @var string $keyPem */
            $keyPem = $keyDetails['key'];
            $loadedKey = PublicKeyLoader::load($keyPem);

            if (!$loadedKey instanceof RSAPublicKey) {
                throw new ValidationException('Expected RSA public key for RSA-PSS verification');
            }

            // Configure for RSA-PSS with proper parameters
            // @phpstan-ignore-next-line (phpseclib fluent interface returns self but typed as mixed)
            $rsaKey = $loadedKey
                ->withHash($hashAlgo)
                ->withMGFHash($hashAlgo)
                ->withPadding(RSA::SIGNATURE_PSS)
                ->withSaltLength($saltLength);

            // Verify the signature
            // @phpstan-ignore method.nonObject (phpseclib fluent interface)
            if (!$rsaKey->verify($signedData, $signature)) {
                throw new ValidationException('RSA-PSS signature verification failed');
            }
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new ValidationException('RSA-PSS signature verification error: ' . $e->getMessage());
        }
    }

    private function mapSignatureAlgorithm(string $algorithm): int
    {
        return match (strtoupper($algorithm)) {
            'SHA256WITHRSA', 'SHA256WITHRSAENCRYPTION' => OPENSSL_ALGO_SHA256,
            'SHA384WITHRSA', 'SHA384WITHRSAENCRYPTION' => OPENSSL_ALGO_SHA384,
            'SHA512WITHRSA', 'SHA512WITHRSAENCRYPTION' => OPENSSL_ALGO_SHA512,
            'SHA256WITHECDSA' => OPENSSL_ALGO_SHA256,
            'SHA384WITHECDSA' => OPENSSL_ALGO_SHA384,
            'SHA512WITHECDSA' => OPENSSL_ALGO_SHA512,
            default => throw new ValidationException('Unsupported signature algorithm: ' . $algorithm),
        };
    }

    /**
     * Decode URL-safe base64 (RFC 4648).
     * Converts -_ to +/ and adds padding if needed.
     *
     * @throws ValidationException
     */
    private function decodeUrlSafeBase64(string $data, bool $allowEmpty = false): string
    {
        if ($data === '') {
            if ($allowEmpty) {
                return '';
            }
            throw new ValidationException('Cannot decode empty base64 string');
        }

        // Convert URL-safe chars to standard base64
        $base64 = strtr($data, '-_', '+/');

        // Add padding if needed
        $padding = strlen($base64) % 4;
        if ($padding > 0) {
            $base64 .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($base64, true);

        if ($decoded === false) {
            throw new ValidationException('Invalid URL-safe base64 encoded data');
        }

        return $decoded;
    }

    /**
     * @throws ValidationException
     */
    private function extractIdentity(SessionCertificate $cert): AuthenticationIdentity
    {
        $certPem = $cert->getPemEncodedCertificate();
        $certResource = openssl_x509_read($certPem);

        if ($certResource === false) {
            throw new ValidationException('Failed to parse certificate for identity extraction');
        }

        $certInfo = openssl_x509_parse($certResource);
        if ($certInfo === false || !isset($certInfo['subject'])) {
            throw new ValidationException('Failed to parse certificate subject');
        }

        /** @var array<string, string|array<int, string>> $subject */
        $subject = $certInfo['subject'];

        $givenName = $this->getSubjectField($subject, ['GN', 'givenName']);
        $surname = $this->getSubjectField($subject, ['SN', 'surname']);
        $serialNumber = $this->getSubjectField($subject, ['serialNumber']);

        $country = '';
        $identityCode = '';

        if ($serialNumber !== '') {
            if (preg_match('/^PNO([A-Z]{2})-(.+)$/', $serialNumber, $matches)) {
                $country = $matches[1];
                $identityCode = $matches[2];
            } elseif (preg_match('/^([A-Z]{2})(.+)$/', $serialNumber, $matches)) {
                $country = $matches[1];
                $identityCode = $matches[2];
            } else {
                $identityCode = $serialNumber;
            }
        }

        if ($country === '') {
            $country = $this->getSubjectField($subject, ['C']);
        }

        return new AuthenticationIdentity(
            givenName: $givenName,
            surname: $surname,
            identityCode: $identityCode,
            country: $country,
        );
    }

    /**
     * Get a field from the certificate subject, checking multiple possible keys.
     *
     * @param array<string, string|array<int, string>> $subject
     * @param string[] $keys
     */
    private function getSubjectField(array $subject, array $keys): string
    {
        foreach ($keys as $key) {
            if (isset($subject[$key])) {
                $value = $subject[$key];
                if (is_array($value)) {
                    return (string) ($value[0] ?? '');
                }

                return (string) $value;
            }
        }

        return '';
    }
}
