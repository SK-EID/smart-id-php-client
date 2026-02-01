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

use Sk\SmartId\Enum\CertificateLevel;
use Sk\SmartId\Exception\SmartIdException;
use Sk\SmartId\Exception\ValidationException;
use Sk\SmartId\Model\AuthenticationIdentity;
use Sk\SmartId\Session\SessionCertificate;
use Sk\SmartId\Session\SessionStatus;

class AuthenticationResponseValidator
{
    /** @var string[] */
    private array $trustedCaCertificates = [];

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
        $this->verifySignature($sessionStatus, $rpChallenge);

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
                sprintf('Unknown certificate level: %s', $cert->getCertificateLevel())
            );
        }

        if (!$actualLevel->meetsRequirement($required)) {
            throw new ValidationException(
                sprintf(
                    'Certificate level %s does not meet required level %s',
                    $actualLevel->value,
                    $required->value,
                )
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

        $algorithm = $this->mapSignatureAlgorithm($signature->getSignatureAlgorithm());

        $signatureValue = $signature->getDecodedValue();

        $result = openssl_verify($decodedChallenge, $signatureValue, $publicKey, $algorithm);

        if ($result === -1) {
            throw new ValidationException('Signature verification error: ' . openssl_error_string());
        }

        if ($result !== 1) {
            throw new ValidationException('Signature verification failed');
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

        $subject = $certInfo['subject'];

        $givenName = $subject['GN'] ?? $subject['givenName'] ?? '';
        $surname = $subject['SN'] ?? $subject['surname'] ?? '';
        $serialNumber = $subject['serialNumber'] ?? '';

        $country = '';
        $identityCode = '';

        if (!empty($serialNumber)) {
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

        if (empty($country) && isset($subject['C'])) {
            $country = $subject['C'];
        }

        return new AuthenticationIdentity(
            givenName: $givenName,
            surname: $surname,
            identityCode: $identityCode,
            country: $country,
        );
    }
}
