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

namespace Sk\SmartId\Tests\Validation;

use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\RSA\PrivateKey as RSAPrivateKey;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\Enum\CertificateLevel;
use Sk\SmartId\Exception\ValidationException;
use Sk\SmartId\Session\SessionCertificate;
use Sk\SmartId\Session\SessionResult;
use Sk\SmartId\Session\SessionSignature;
use Sk\SmartId\Session\SessionStatus;
use Sk\SmartId\Session\SignatureAlgorithmParameters;
use Sk\SmartId\Validation\AuthenticationResponseValidator;
use Sk\SmartId\Validation\OcspCertificateRevocationChecker;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;

class AuthenticationResponseValidatorTest extends TestCase
{
    private const TEST_RP_CHALLENGE = 'dGVzdC1ycC1jaGFsbGVuZ2U=';

    private const TEST_RP_NAME = 'DEMO';

    private const TEST_INTERACTIONS_BASE64 = 'aW50ZXJhY3Rpb25z';

    private const TEST_SERVER_RANDOM = 'test-server-random';

    private const TEST_USER_CHALLENGE = 'test-user-challenge';

    private const TEST_FLOW_TYPE = 'deviceLink';

    private const TEST_INTERACTION_TYPE_USED = 'displayTextAndPIN';

    private static function getTestEndEntityCertsDir(): string
    {
        return dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'resources'
            . DIRECTORY_SEPARATOR . 'test_end_entity_certs';
    }

    private static function getTestCaCertPem(): string
    {
        return file_get_contents(self::getTestEndEntityCertsDir() . DIRECTORY_SEPARATOR . 'test_ca.pem.crt');
    }

    /**
     * Extract the base64 cert body from PEM (strip headers/whitespace) as SessionCertificate expects.
     */
    private static function pemToBase64(string $pemFilePath): string
    {
        $pem = file_get_contents($pemFilePath);
        $pem = str_replace(['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----'], '', $pem);

        return str_replace(["\r", "\n", ' '], '', $pem);
    }

    /**
     * Build a SessionStatus with a real RSA-PSS ACSP_V2 signature using the EE cert's private key.
     */
    private static function createSignedSessionStatus(string $certBaseName, string $certLevel): SessionStatus
    {
        $dir = self::getTestEndEntityCertsDir();
        $eeCertBase64 = self::pemToBase64($dir . DIRECTORY_SEPARATOR . $certBaseName . '.pem.crt');
        $keyPem = file_get_contents($dir . DIRECTORY_SEPARATOR . $certBaseName . '.key.pem');

        $interactionsHash = hash('sha256', self::TEST_INTERACTIONS_BASE64, true);
        $interactionsHashBase64 = base64_encode($interactionsHash);

        $acspV2Payload = implode('|', [
            'smart-id',
            'ACSP_V2',
            self::TEST_SERVER_RANDOM,
            self::TEST_RP_CHALLENGE,
            self::TEST_USER_CHALLENGE,
            base64_encode(self::TEST_RP_NAME),
            base64_encode(''),
            $interactionsHashBase64,
            self::TEST_INTERACTION_TYPE_USED,
            '',
            self::TEST_FLOW_TYPE,
        ]);

        $loaded = PublicKeyLoader::load($keyPem);
        assert($loaded instanceof RSAPrivateKey);

        // @phpstan-ignore-next-line
        $rsaKey = $loaded
            ->withHash('sha256')
            ->withMGFHash('sha256')
            ->withPadding(RSA::SIGNATURE_PSS)
            ->withSaltLength(32);

        // @phpstan-ignore method.nonObject
        $signatureBytes = $rsaKey->sign($acspV2Payload);
        $signatureBase64 = base64_encode($signatureBytes);

        $algorithmParams = new SignatureAlgorithmParameters(
            hashAlgorithm: 'SHA-256',
            saltLength: 32,
            maskGenAlgorithm: 'id-mgf1',
            maskGenHashAlgorithm: 'SHA-256',
        );

        $signature = new SessionSignature(
            $signatureBase64,
            'rsassa-pss',
            self::TEST_SERVER_RANDOM,
            self::TEST_USER_CHALLENGE,
            self::TEST_FLOW_TYPE,
            parsedAlgorithmParameters: $algorithmParams,
        );

        $result = new SessionResult('OK', 'DOC123');
        $cert = new SessionCertificate($eeCertBase64, $certLevel);

        return new SessionStatus(
            'COMPLETE',
            $result,
            $cert,
            $signature,
            'ACSP_V2',
            interactionTypeUsed: self::TEST_INTERACTION_TYPE_USED,
        );
    }

    #[Test]
    public function setTrustedCaCertificatesReturnsSelf(): void
    {
        $validator = new AuthenticationResponseValidator();

        $result = $validator->setTrustedCaCertificates(['cert1', 'cert2']);

        $this->assertSame($validator, $result);
    }

    #[Test]
    public function addTrustedCaCertificateReturnsSelf(): void
    {
        $validator = new AuthenticationResponseValidator();

        $result = $validator->addTrustedCaCertificate('cert');

        $this->assertSame($validator, $result);
    }

    #[Test]
    public function getTrustedCaCertificatesReturnsConfiguredCertificates(): void
    {
        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates(['cert1', 'cert2']);

        $this->assertSame(['cert1', 'cert2'], $validator->getTrustedCaCertificates());
    }

    #[Test]
    public function addTrustedCaCertificateAppendsCertificate(): void
    {
        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates(['cert1']);
        $validator->addTrustedCaCertificate('cert2');

        $this->assertSame(['cert1', 'cert2'], $validator->getTrustedCaCertificates());
    }

    #[Test]
    public function validateThrowsForIncompleteSession(): void
    {
        $validator = new AuthenticationResponseValidator();
        $status = new SessionStatus('RUNNING');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Cannot validate incomplete session');

        $validator->validate($status, 'rpChallenge', 'DEMO', 'aW50ZXJhY3Rpb25z');
    }

    #[Test]
    public function validateThrowsForNullResult(): void
    {
        $validator = new AuthenticationResponseValidator();
        $status = new SessionStatus('COMPLETE');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Session result is not OK');

        $validator->validate($status, 'rpChallenge', 'DEMO', 'aW50ZXJhY3Rpb25z');
    }

    #[Test]
    public function validateThrowsForNonOkResult(): void
    {
        $validator = new AuthenticationResponseValidator();
        $result = new SessionResult('USER_REFUSED');
        $status = new SessionStatus('COMPLETE', $result);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Session result is not OK');

        $validator->validate($status, 'rpChallenge', 'DEMO', 'aW50ZXJhY3Rpb25z');
    }

    #[Test]
    public function validateThrowsForWrongSignatureProtocol(): void
    {
        $validator = new AuthenticationResponseValidator();
        $result = new SessionResult('OK', 'DOC123');
        $status = new SessionStatus('COMPLETE', $result, null, null, 'WRONG_PROTOCOL');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Expected signatureProtocol ACSP_V2, got: WRONG_PROTOCOL');

        $validator->validate($status, 'rpChallenge', 'DEMO', 'aW50ZXJhY3Rpb25z');
    }

    #[Test]
    public function validateThrowsForMissingSignatureProtocol(): void
    {
        $validator = new AuthenticationResponseValidator();
        $result = new SessionResult('OK', 'DOC123');
        $status = new SessionStatus('COMPLETE', $result);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Expected signatureProtocol ACSP_V2, got: null');

        $validator->validate($status, 'rpChallenge', 'DEMO', 'aW50ZXJhY3Rpb25z');
    }

    #[Test]
    public function validateThrowsForMissingCertificate(): void
    {
        $validator = new AuthenticationResponseValidator();
        $result = new SessionResult('OK', 'DOC123');
        $status = new SessionStatus('COMPLETE', $result, null, null, 'ACSP_V2');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Certificate is missing from session response');

        $validator->validate($status, 'rpChallenge', 'DEMO', 'aW50ZXJhY3Rpb25z');
    }

    #[Test]
    public function validateThrowsForMissingSignature(): void
    {
        $validator = new AuthenticationResponseValidator();
        $result = new SessionResult('OK', 'DOC123');
        $cert = new SessionCertificate('certValue', 'QUALIFIED');
        $status = new SessionStatus('COMPLETE', $result, $cert, null, 'ACSP_V2');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Signature is missing from session response');

        $validator->validate($status, 'rpChallenge', 'DEMO', 'aW50ZXJhY3Rpb25z');
    }

    #[Test]
    public function validateThrowsWhenNoTrustedCaCertificatesConfigured(): void
    {
        $validator = new AuthenticationResponseValidator();
        $result = new SessionResult('OK', 'DOC123');
        $cert = new SessionCertificate('certValue', 'QUALIFIED');
        $signature = new SessionSignature(base64_encode('sig'), 'rsassa-pss');
        $status = new SessionStatus('COMPLETE', $result, $cert, $signature, 'ACSP_V2');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('No trusted CA certificates configured');

        $validator->validate($status, base64_encode('challenge'), 'DEMO', 'aW50ZXJhY3Rpb25z');
    }

    #[Test]
    public function validateThrowsWhenCertificateLevelDoesNotMeetRequirement(): void
    {
        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates(['dummy-cert']);
        $result = new SessionResult('OK', 'DOC123');
        $cert = new SessionCertificate('certValue', 'ADVANCED');
        $signature = new SessionSignature(base64_encode('sig'), 'rsassa-pss');
        $status = new SessionStatus('COMPLETE', $result, $cert, $signature, 'ACSP_V2');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Certificate level ADVANCED does not meet required level QUALIFIED');

        $validator->validate(
            $status,
            base64_encode('challenge'),
            'DEMO',
            'aW50ZXJhY3Rpb25z',
            null,
            null,
            CertificateLevel::QUALIFIED,
        );
    }

    #[Test]
    public function validateThrowsForUnknownCertificateLevel(): void
    {
        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates(['dummy-cert']);
        $result = new SessionResult('OK', 'DOC123');
        $cert = new SessionCertificate('certValue', 'UNKNOWN_LEVEL');
        $signature = new SessionSignature(base64_encode('sig'), 'rsassa-pss');
        $status = new SessionStatus('COMPLETE', $result, $cert, $signature, 'ACSP_V2');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Unknown certificate level: UNKNOWN_LEVEL');

        $validator->validate(
            $status,
            base64_encode('challenge'),
            'DEMO',
            'aW50ZXJhY3Rpb25z',
            null,
            null,
            CertificateLevel::QUALIFIED,
        );
    }

    #[Test]
    public function validatePassesCertificatePoliciesForQualifiedSmartId(): void
    {
        $caCertPem = self::getTestCaCertPem();
        $status = self::createSignedSessionStatus('qualified_smartid_ee', 'QUALIFIED');

        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates([$caCertPem]);

        // Test cert has both Smart-ID qualified policy OIDs and clientAuth EKU,
        // so policies and purpose checks both pass. validate() should succeed.
        $identity = $validator->validate($status, self::TEST_RP_CHALLENGE, self::TEST_RP_NAME, self::TEST_INTERACTIONS_BASE64);

        $this->assertSame('QUALIFIED', $identity->getGivenName());
        $this->assertSame('TESTNUMBER', $identity->getSurname());
        $this->assertSame('30303039914', $identity->getIdentityCode());
        $this->assertSame('EE', $identity->getCountry());
    }

    #[Test]
    public function validatePassesCertificatePoliciesForNonQualifiedSmartId(): void
    {
        $caCertPem = self::getTestCaCertPem();
        $status = self::createSignedSessionStatus('non_qualified_smartid_ee', 'ADVANCED');

        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates([$caCertPem]);

        // Test cert has non-qualified Smart-ID policy OID and clientAuth EKU,
        // so policies and purpose checks both pass. validate() should succeed.
        $identity = $validator->validate($status, self::TEST_RP_CHALLENGE, self::TEST_RP_NAME, self::TEST_INTERACTIONS_BASE64);

        $this->assertSame('NONQUALIFIED', $identity->getGivenName());
        $this->assertSame('TESTNUMBER', $identity->getSurname());
        $this->assertSame('30303039915', $identity->getIdentityCode());
        $this->assertSame('EE', $identity->getCountry());
    }

    #[Test]
    public function validateThrowsForNonSmartIdCertificatePolicies(): void
    {
        $caCertPem = self::getTestCaCertPem();
        $status = self::createSignedSessionStatus('non_smartid_ee', 'QUALIFIED');

        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates([$caCertPem]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Smart-ID scheme Certificate Policy OIDs');

        $validator->validate($status, self::TEST_RP_CHALLENGE, self::TEST_RP_NAME, self::TEST_INTERACTIONS_BASE64);
    }

    #[Test]
    public function validateThrowsForCertificateWithNoPolicies(): void
    {
        $caCertPem = self::getTestCaCertPem();
        $status = self::createSignedSessionStatus('no_policies_ee', 'QUALIFIED');

        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates([$caCertPem]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Smart-ID scheme Certificate Policy OIDs');

        $validator->validate($status, self::TEST_RP_CHALLENGE, self::TEST_RP_NAME, self::TEST_INTERACTIONS_BASE64);
    }

    #[Test]
    public function validateThrowsForQualifiedCertMissingEuQcpPolicy(): void
    {
        $caCertPem = self::getTestCaCertPem();
        $status = self::createSignedSessionStatus('qualified_missing_euqcp_ee', 'QUALIFIED');

        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates([$caCertPem]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('EU QCP policy OID 0.4.0.2042.1.2');

        $validator->validate($status, self::TEST_RP_CHALLENGE, self::TEST_RP_NAME, self::TEST_INTERACTIONS_BASE64);
    }

    #[Test]
    public function setOcspRevocationCheckerReturnsSelf(): void
    {
        $validator = new AuthenticationResponseValidator();
        $factory = new HttpFactory();
        $checker = new OcspCertificateRevocationChecker(new Client(), $factory, $factory);

        $result = $validator->setOcspRevocationChecker($checker);

        $this->assertSame($validator, $result);
    }

    #[Test]
    public function setOcspRevocationCheckerWithNullReturnsSelf(): void
    {
        $validator = new AuthenticationResponseValidator();

        $result = $validator->setOcspRevocationChecker(null);

        $this->assertSame($validator, $result);
    }

    #[Test]
    public function validateThrowsForCaCertificateUsedAsEndEntity(): void
    {
        // Use the test CA cert (has CA:TRUE) as if it were an end-entity cert
        // CA cert has no private key saved, so we must construct the status manually.
        // The basic constraints check happens before signature verification,
        // so the signature value doesn't matter — it will throw before reaching it.
        $caCertPem = self::getTestCaCertPem();
        $caCertBase64 = self::pemToBase64(self::getTestEndEntityCertsDir() . DIRECTORY_SEPARATOR . 'test_ca.pem.crt');

        $validator = new AuthenticationResponseValidator();
        // Trust itself (self-signed)
        $validator->setTrustedCaCertificates([$caCertPem]);

        $result = new SessionResult('OK', 'DOC123');
        $cert = new SessionCertificate($caCertBase64, 'QUALIFIED');
        $signature = new SessionSignature(base64_encode('sig'), 'rsassa-pss');
        $status = new SessionStatus('COMPLETE', $result, $cert, $signature, 'ACSP_V2');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('CA:TRUE');

        $validator->validate($status, self::TEST_RP_CHALLENGE, self::TEST_RP_NAME, self::TEST_INTERACTIONS_BASE64);
    }

    #[Test]
    public function validatePassesBasicConstraintsForEndEntityCert(): void
    {
        $caCertPem = self::getTestCaCertPem();
        $status = self::createSignedSessionStatus('qualified_smartid_ee', 'QUALIFIED');

        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates([$caCertPem]);

        // EE cert has CA:FALSE — should pass basic constraints and all other checks
        $identity = $validator->validate($status, self::TEST_RP_CHALLENGE, self::TEST_RP_NAME, self::TEST_INTERACTIONS_BASE64);

        $this->assertSame('QUALIFIED', $identity->getGivenName());
    }

    #[Test]
    public function verifySessionSecretPassesForValidDigest(): void
    {
        $validator = new AuthenticationResponseValidator();

        $sessionSecret = base64_encode('test-session-secret-value');
        $hash = hash('sha256', base64_decode($sessionSecret), true);
        $digest = rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');

        // Should not throw
        $validator->verifySessionSecret($sessionSecret, $digest);
        $this->assertTrue(true);
    }

    #[Test]
    public function verifySessionSecretThrowsForMismatch(): void
    {
        $validator = new AuthenticationResponseValidator();

        $sessionSecret = base64_encode('test-session-secret-value');
        $wrongDigest = 'wrong-digest-value';

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('sessionSecretDigest');

        $validator->verifySessionSecret($sessionSecret, $wrongDigest);
    }

    #[Test]
    public function verifySessionSecretThrowsForInvalidBase64(): void
    {
        $validator = new AuthenticationResponseValidator();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Failed to Base64-decode sessionSecret');

        $validator->verifySessionSecret('!!!not-base64!!!', 'some-digest');
    }

    #[Test]
    public function verifyUserChallengePassesForValidMatch(): void
    {
        $validator = new AuthenticationResponseValidator();

        $verifier = 'test-user-challenge-verifier';
        $hash = hash('sha256', $verifier, true);
        $expected = rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');

        // Should not throw
        $validator->verifyUserChallenge($verifier, $expected);
        $this->assertTrue(true);
    }

    #[Test]
    public function verifyUserChallengeThrowsForMismatch(): void
    {
        $validator = new AuthenticationResponseValidator();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('userChallenge');

        $validator->verifyUserChallenge('verifier', 'wrong-challenge-value');
    }
}
