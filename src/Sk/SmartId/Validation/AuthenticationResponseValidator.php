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
use Sk\SmartId\Enum\SchemeName;
use Sk\SmartId\Exception\SmartIdException;
use Sk\SmartId\Exception\ValidationException;
use Sk\SmartId\Model\AuthenticationIdentity;
use Sk\SmartId\Session\SessionCertificate;
use Sk\SmartId\Session\SessionSignature;
use Sk\SmartId\Session\SessionStatus;
use Sk\SmartId\Util\Base64Url;

class AuthenticationResponseValidator
{
    /** @var string[] PEM-encoded CA certificates */
    private array $trustedCaCertificates = [];

    /** @var string[] File paths to trusted CA certificate files */
    private array $trustedCaCertificateFiles = [];

    private ?OcspCertificateRevocationChecker $ocspChecker = null;

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

    /**
     * @param string[] $files Array of file paths to trusted CA certificate files
     */
    public function setTrustedCaCertificateFiles(array $files): self
    {
        $this->trustedCaCertificateFiles = $files;

        return $this;
    }

    public function addTrustedCaCertificate(string $certificate): self
    {
        $this->trustedCaCertificates[] = $certificate;

        return $this;
    }

    public function setOcspRevocationChecker(?OcspCertificateRevocationChecker $checker): self
    {
        $this->ocspChecker = $checker;

        return $this;
    }

    /**
     * Verify session secret for Web2App/App2App flows.
     * Must be called BEFORE validate() when using Web2App or App2App flows.
     *
     * Verifies that SHA-256(Base64Decode(sessionSecret)) encoded as Base64URL
     * matches the sessionSecretDigest parameter from the callback URL.
     *
     * @param string $sessionSecret The sessionSecret from the initial session response (Base64-encoded)
     * @param string $sessionSecretDigest The sessionSecretDigest from the callback URL
     * @throws ValidationException if the digest does not match
     */
    public function verifySessionSecret(string $sessionSecret, string $sessionSecretDigest): void
    {
        $decoded = base64_decode($sessionSecret, true);
        if ($decoded === false) {
            throw new ValidationException('Failed to Base64-decode sessionSecret');
        }

        $hash = hash('sha256', $decoded, true);
        $expected = Base64Url::encode($hash);

        if (!hash_equals($expected, $sessionSecretDigest)) {
            throw new ValidationException(
                'sessionSecretDigest from callback URL does not match SHA-256 of sessionSecret',
            );
        }
    }

    /**
     * Verify user challenge for Web2App/App2App authentication flows.
     * Must be called when using Web2App or App2App flows.
     *
     * Verifies that SHA-256(userChallengeVerifier) encoded as Base64URL
     * matches the signature.userChallenge from the session response.
     *
     * @param string $userChallengeVerifier The userChallengeVerifier from the callback URL
     * @param string $userChallengeFromResponse The signature.userChallenge from the session status response
     * @throws ValidationException if the values do not match
     */
    public function verifyUserChallenge(string $userChallengeVerifier, string $userChallengeFromResponse): void
    {
        $hash = hash('sha256', $userChallengeVerifier, true);
        $expected = Base64Url::encode($hash);

        if (!hash_equals($expected, $userChallengeFromResponse)) {
            throw new ValidationException(
                'userChallenge from session response does not match SHA-256 of userChallengeVerifier from callback URL',
            );
        }
    }

    /**
     * @param string $rpChallenge Base64-encoded RP challenge sent in the initial request
     * @param string $relyingPartyName Relying Party name as sent in the initial request
     * @param string $interactionsBase64 Base64-encoded interactions JSON as sent in the initial request
     * @param string|null $initialCallbackUrl Callback URL if Web2App flow was used, null otherwise
     * @param string|null $brokeredRpName Brokered RP name if acting as broker, null otherwise
     * @param SchemeName $schemeName Scheme name for the target environment
     *
     * @throws SmartIdException
     * @throws ValidationException
     */
    public function validate(
        SessionStatus $sessionStatus,
        string $rpChallenge,
        string $relyingPartyName,
        string $interactionsBase64,
        ?string $initialCallbackUrl = null,
        ?string $brokeredRpName = null,
        ?CertificateLevel $requiredCertificateLevel = null,
        SchemeName $schemeName = SchemeName::PRODUCTION,
    ): AuthenticationIdentity {
        if (!$sessionStatus->isComplete()) {
            throw new ValidationException('Cannot validate incomplete session');
        }

        $result = $sessionStatus->getResult();
        if ($result === null || !$result->isOk()) {
            throw new ValidationException('Session result is not OK');
        }

        $signatureProtocol = $sessionStatus->getSignatureProtocol();
        if ($signatureProtocol !== 'ACSP_V2') {
            throw new ValidationException(
                sprintf('Expected signatureProtocol ACSP_V2, got: %s', $signatureProtocol ?? 'null'),
            );
        }

        $cert = $sessionStatus->getCert();
        if ($cert === null) {
            throw new ValidationException('Certificate is missing from session response');
        }

        $signature = $sessionStatus->getSignature();
        if ($signature === null) {
            throw new ValidationException('Signature is missing from session response');
        }

        if (empty($this->trustedCaCertificateFiles) && empty($this->trustedCaCertificates)) {
            throw new ValidationException('No trusted CA certificates configured');
        }

        if ($requiredCertificateLevel !== null) {
            $this->verifyCertificateLevel($cert, $requiredCertificateLevel);
        }

        $certPem = $cert->getPemEncodedCertificate();
        $certInfo = $this->parseCertificate($certPem);

        $this->verifyCertificateTrust($certPem, $certInfo);
        $this->verifyBasicConstraints($certInfo);
        $this->verifyCertificateRevocation($cert);
        $this->verifyCertificatePolicies($certInfo);
        $this->verifyCertificatePurpose($certInfo);

        $this->verifySignature(
            $sessionStatus,
            $rpChallenge,
            $relyingPartyName,
            $interactionsBase64,
            $initialCallbackUrl,
            $brokeredRpName,
            $schemeName,
        );

        return $this->extractIdentity($certInfo);
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
     * Parse a PEM certificate and return its parsed info array.
     *
     * @return array<string, mixed>
     * @throws ValidationException
     */
    private function parseCertificate(string $certPem): array
    {
        $certResource = openssl_x509_read($certPem);
        if ($certResource === false) {
            throw new ValidationException('Failed to parse certificate');
        }

        $certInfo = openssl_x509_parse($certResource);
        if ($certInfo === false) {
            throw new ValidationException('Failed to parse certificate information');
        }

        return $certInfo;
    }

    /**
     * @param array<string, mixed> $certInfo
     * @throws ValidationException
     */
    private function verifyCertificateTrust(string $certPem, array $certInfo): void
    {
        $now = time();
        if (isset($certInfo['validFrom_time_t']) && $certInfo['validFrom_time_t'] > $now) {
            throw new ValidationException('Certificate is not yet valid');
        }

        if (isset($certInfo['validTo_time_t']) && $certInfo['validTo_time_t'] < $now) {
            throw new ValidationException('Certificate has expired');
        }

        // Use openssl_x509_checkpurpose for proper certificate chain validation.
        // Unlike openssl_x509_verify (direct issuer check only), this builds the
        // full chain from leaf through intermediates to root trust anchors.
        $caFiles = $this->trustedCaCertificateFiles;
        $tempFiles = [];

        // If only PEM strings available (no file paths), write to temp files
        if (empty($caFiles) && !empty($this->trustedCaCertificates)) {
            foreach ($this->trustedCaCertificates as $pemCert) {
                $tmpFile = tempnam(sys_get_temp_dir(), 'smartid_ca_');
                if ($tmpFile === false) {
                    throw new ValidationException('Failed to create temporary file for CA certificate');
                }
                if (file_put_contents($tmpFile, $pemCert) === false) {
                    throw new ValidationException('Failed to write CA certificate to temporary file');
                }
                $caFiles[] = $tmpFile;
                $tempFiles[] = $tmpFile;
            }
        }

        try {
            $result = openssl_x509_checkpurpose($certPem, X509_PURPOSE_ANY, $caFiles);

            if ($result !== true) {
                throw new ValidationException('Certificate is not signed by a trusted CA');
            }
        } finally {
            foreach ($tempFiles as $tmpFile) {
                @unlink($tmpFile);
            }
        }
    }

    /**
     * Verify that the end-entity certificate has appropriate basic constraints.
     * Per Smart-ID docs: end-entity certificates must either not have the Basic Constraints
     * extension, or if present, the cA boolean must be set to FALSE.
     *
     * @param array<string, mixed> $certInfo
     * @throws ValidationException
     */
    private function verifyBasicConstraints(array $certInfo): void
    {
        $basicConstraints = $certInfo['extensions']['basicConstraints'] ?? null;

        // If basicConstraints is absent, that's fine for an EE certificate
        if ($basicConstraints === null) {
            return;
        }

        // If present, CA must be FALSE
        if (stripos($basicConstraints, 'CA:TRUE') !== false) {
            throw new ValidationException(
                'End-entity certificate has CA:TRUE in Basic Constraints — this is a CA certificate, not an end-entity certificate',
            );
        }
    }

    private function verifyCertificateRevocation(SessionCertificate $cert): void
    {
        if ($this->ocspChecker === null) {
            return;
        }

        $certPem = $cert->getPemEncodedCertificate();

        $issuerPem = $this->findIssuerCertificate($certPem);
        if ($issuerPem === null) {
            throw new ValidationException(
                'Cannot perform OCSP check: no matching issuer certificate found in trusted CA store',
            );
        }

        $this->ocspChecker->checkRevocationStatus($certPem, $issuerPem);
    }

    /**
     * Find the issuer certificate for the given end-entity certificate from the trusted CA store.
     */
    private function findIssuerCertificate(string $certPem): ?string
    {
        $certResource = openssl_x509_read($certPem);
        if ($certResource === false) {
            return null;
        }

        $pemCerts = $this->trustedCaCertificates;

        // If no PEM strings available, read from file paths
        if (empty($pemCerts) && !empty($this->trustedCaCertificateFiles)) {
            foreach ($this->trustedCaCertificateFiles as $file) {
                $content = file_get_contents($file);
                if ($content !== false) {
                    $pemCerts[] = $content;
                }
            }
        }

        foreach ($pemCerts as $caCert) {
            if (openssl_x509_verify($certResource, $caCert) === 1) {
                return $caCert;
            }
        }

        return null;
    }

    /**
     * Verify that the end-entity certificate belongs to the Smart-ID scheme
     * by checking Certificate Policies extension for required OIDs.
     *
     * Qualified Smart-ID certs must have:
     *   - 1.3.6.1.4.1.10015.17.2 (SK Smart-ID Qualified policy)
     *   - 0.4.0.2042.1.2 (EU QCP for qualified electronic signatures)
     *
     * Non-qualified Smart-ID certs must have:
     *   - 1.3.6.1.4.1.10015.17.1 (SK Smart-ID Non-Qualified policy)
     *
     * @param array<string, mixed> $certInfo
     * @throws ValidationException
     */
    private function verifyCertificatePolicies(array $certInfo): void
    {
        $policies = $certInfo['extensions']['certificatePolicies'] ?? '';

        $hasQualifiedPolicy = str_contains($policies, '1.3.6.1.4.1.10015.17.2');
        $hasNonQualifiedPolicy = str_contains($policies, '1.3.6.1.4.1.10015.17.1');

        if (!$hasQualifiedPolicy && !$hasNonQualifiedPolicy) {
            throw new ValidationException(
                'Certificate does not contain Smart-ID scheme Certificate Policy OIDs '
                . '(expected 1.3.6.1.4.1.10015.17.2 for Qualified or 1.3.6.1.4.1.10015.17.1 for Non-Qualified)',
            );
        }

        if ($hasQualifiedPolicy && !str_contains($policies, '0.4.0.2042.1.2')) {
            throw new ValidationException(
                'Qualified Smart-ID certificate is missing EU QCP policy OID 0.4.0.2042.1.2',
            );
        }
    }

    /**
     * @param array<string, mixed> $certInfo
     * @throws ValidationException
     */
    private function verifyCertificatePurpose(array $certInfo): void
    {
        // Check key usage includes digitalSignature
        $keyUsage = $certInfo['extensions']['keyUsage'] ?? '';
        if (stripos($keyUsage, 'Digital Signature') === false) {
            throw new ValidationException('Certificate key usage does not include digitalSignature');
        }

        // Check Extended Key Usage for Smart-ID authentication
        // New certs (April 2025+): Smart-ID authentication OID 1.3.6.1.4.1.62306.5.7.0
        // Older certs: id-kp-clientAuth OID 1.3.6.1.5.5.7.3.2
        $extKeyUsage = $certInfo['extensions']['extendedKeyUsage'] ?? '';
        $hasSmartIdAuthEku = str_contains($extKeyUsage, '1.3.6.1.4.1.62306.5.7.0');
        $hasClientAuthEku = str_contains($extKeyUsage, '1.3.6.1.5.5.7.3.2')
            || stripos($extKeyUsage, 'TLS Web Client Authentication') !== false;

        if (!$hasSmartIdAuthEku && !$hasClientAuthEku) {
            throw new ValidationException(
                'Certificate does not have Smart-ID authentication or clientAuth Extended Key Usage',
            );
        }
    }

    /**
     * @throws ValidationException
     */
    private function verifySignature(
        SessionStatus $sessionStatus,
        string $rpChallenge,
        string $relyingPartyName,
        string $interactionsBase64,
        ?string $initialCallbackUrl,
        ?string $brokeredRpName,
        SchemeName $schemeName,
    ): void {
        $cert = $sessionStatus->getCert();
        $signature = $sessionStatus->getSignature();

        if ($cert === null || $signature === null) {
            throw new ValidationException('Certificate or signature missing');
        }

        $algorithmName = strtolower($signature->getSignatureAlgorithm());
        if ($algorithmName !== 'rsassa-pss') {
            throw new ValidationException(
                sprintf('Expected rsassa-pss signature algorithm for ACSP_V2, got: %s', $algorithmName),
            );
        }

        $serverRandom = $signature->getServerRandom();
        $userChallenge = $signature->getUserChallenge();
        $flowType = $signature->getFlowType();
        $interactionTypeUsed = $sessionStatus->getInteractionTypeUsed();

        if ($serverRandom === null || $serverRandom === '') {
            throw new ValidationException('serverRandom is required for ACSP_V2 signature verification');
        }
        if ($userChallenge === null || $userChallenge === '') {
            throw new ValidationException('userChallenge is required for ACSP_V2 signature verification');
        }
        if ($flowType === null || $flowType === '') {
            throw new ValidationException('flowType is required for ACSP_V2 signature verification');
        }
        if ($interactionTypeUsed === null || $interactionTypeUsed === '') {
            throw new ValidationException('interactionTypeUsed is required for ACSP_V2 signature verification');
        }

        // Construct ACSP_V2 payload per documentation:
        // 'smart-id'|'ACSP_V2'|serverRandom|rpChallenge|userChallenge|BASE64(rpName)|BASE64(brokeredRpName)|BASE64(SHA-256(interactions))|interactionTypeUsed|initialCallbackUrl|flowType
        $interactionsHash = hash('sha256', $interactionsBase64, true);
        $interactionsHashBase64 = base64_encode($interactionsHash);

        $acspV2Payload = implode('|', [
            $schemeName->value,
            'ACSP_V2',
            $serverRandom,
            $rpChallenge,
            $userChallenge,
            base64_encode($relyingPartyName),
            base64_encode($brokeredRpName ?? ''),
            $interactionsHashBase64,
            $interactionTypeUsed,
            $initialCallbackUrl ?? '',
            $flowType,
        ]);

        $this->verifyRsaPssSignature($acspV2Payload, $signature, $cert);
    }

    /**
     * @throws ValidationException
     */
    private function verifyRsaPssSignature(
        string $acspV2Payload,
        SessionSignature $sessionSignature,
        SessionCertificate $cert,
    ): void {
        $parsed = $sessionSignature->getParsedAlgorithmParameters();
        if ($parsed === null) {
            throw new ValidationException('signatureAlgorithmParameters is missing from signature');
        }

        $resolved = $parsed->getResolvedHashAlgorithm();
        if ($resolved === null) {
            throw new ValidationException(
                "Unsupported hash algorithm for RSA-PSS: {$parsed->getHashAlgorithm()}",
            );
        }
        $hashAlgo = $resolved->getDigestAlgorithm();

        $saltLength = $parsed->getSaltLength();
        if ($saltLength === null) {
            throw new ValidationException('signatureAlgorithmParameters.saltLength is missing');
        }
        if ($saltLength !== $resolved->getHashLength()) {
            throw new ValidationException(
                sprintf(
                    'signatureAlgorithmParameters.saltLength %d does not match hash octet length %d for %s',
                    $saltLength,
                    $resolved->getHashLength(),
                    $resolved->value,
                ),
            );
        }

        if ($parsed->getMaskGenAlgorithm() !== null && $parsed->getMaskGenAlgorithm() !== 'id-mgf1') {
            throw new ValidationException(
                sprintf('Unsupported maskGenAlgorithm: %s', $parsed->getMaskGenAlgorithm()),
            );
        }

        if ($parsed->getMaskGenHashAlgorithm() !== null) {
            $mgfHash = \Sk\SmartId\Enum\HashAlgorithm::fromString($parsed->getMaskGenHashAlgorithm());
            if ($mgfHash === null) {
                throw new ValidationException(
                    sprintf('Unsupported maskGenAlgorithm hash: %s', $parsed->getMaskGenHashAlgorithm()),
                );
            }
            if ($mgfHash !== $resolved) {
                throw new ValidationException(
                    'maskGenAlgorithm hashAlgorithm does not match signatureAlgorithmParameters hashAlgorithm',
                );
            }
        }

        if ($parsed->getTrailerField() !== null
            && strcasecmp($parsed->getTrailerField(), 'BC') !== 0
            && strcasecmp($parsed->getTrailerField(), '0xBC') !== 0
        ) {
            throw new ValidationException(
                sprintf('Unsupported trailerField: %s', $parsed->getTrailerField()),
            );
        }

        $signatureValue = $sessionSignature->getDecodedValue();

        $certPem = $cert->getPemEncodedCertificate();
        $publicKey = openssl_pkey_get_public($certPem);

        if ($publicKey === false) {
            throw new ValidationException('Failed to extract public key from certificate');
        }

        $keyDetails = openssl_pkey_get_details($publicKey);
        if ($keyDetails === false || !isset($keyDetails['key'])) {
            throw new ValidationException('Failed to extract public key details');
        }

        try {
            /** @var string $keyPem */
            $keyPem = $keyDetails['key'];
            $loadedKey = PublicKeyLoader::load($keyPem);

            if (!$loadedKey instanceof RSAPublicKey) {
                throw new ValidationException('Expected RSA public key for RSA-PSS verification');
            }

            // @phpstan-ignore-next-line (phpseclib fluent interface returns self but typed as mixed)
            $rsaKey = $loadedKey
                ->withHash($hashAlgo)
                ->withMGFHash($hashAlgo)
                ->withPadding(RSA::SIGNATURE_PSS)
                ->withSaltLength($saltLength);

            // Verify signature against UTF-8 bytes of ACSP_V2 payload
            // phpseclib handles hashing internally as part of RSA-PSS
            // @phpstan-ignore method.nonObject (phpseclib fluent interface)
            if (!$rsaKey->verify($acspV2Payload, $signatureValue)) {
                throw new ValidationException('RSA-PSS signature verification failed');
            }
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new ValidationException('RSA-PSS signature verification error: ' . $e->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $certInfo
     * @throws ValidationException
     */
    private function extractIdentity(array $certInfo): AuthenticationIdentity
    {
        if (!isset($certInfo['subject'])) {
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
