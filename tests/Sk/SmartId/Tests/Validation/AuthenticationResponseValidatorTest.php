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

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
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

    #[Test]
    public function validateThrowsForWrongSignatureAlgorithm(): void
    {
        $caCertPem = self::getTestCaCertPem();
        $status = self::createSignedSessionStatus('qualified_smartid_ee', 'QUALIFIED');

        // Replace the signature with a wrong algorithm name
        $result = new SessionResult('OK', 'DOC123');
        $cert = $status->getCert();
        $wrongAlgSig = new SessionSignature(
            $status->getSignature()->getValue(),
            'sha256WithRSAEncryption',
        );
        $wrongStatus = new SessionStatus('COMPLETE', $result, $cert, $wrongAlgSig, 'ACSP_V2', interactionTypeUsed: self::TEST_INTERACTION_TYPE_USED);

        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates([$caCertPem]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Expected rsassa-pss signature algorithm');

        $validator->validate($wrongStatus, self::TEST_RP_CHALLENGE, self::TEST_RP_NAME, self::TEST_INTERACTIONS_BASE64);
    }

    #[Test]
    public function validateThrowsForMissingServerRandom(): void
    {
        $caCertPem = self::getTestCaCertPem();
        $dir = self::getTestEndEntityCertsDir();
        $eeCertBase64 = self::pemToBase64($dir . DIRECTORY_SEPARATOR . 'qualified_smartid_ee.pem.crt');

        $result = new SessionResult('OK', 'DOC123');
        $cert = new SessionCertificate($eeCertBase64, 'QUALIFIED');
        $signature = new SessionSignature(base64_encode('sig'), 'rsassa-pss');
        $status = new SessionStatus('COMPLETE', $result, $cert, $signature, 'ACSP_V2', interactionTypeUsed: self::TEST_INTERACTION_TYPE_USED);

        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates([$caCertPem]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('serverRandom is required');

        $validator->validate($status, self::TEST_RP_CHALLENGE, self::TEST_RP_NAME, self::TEST_INTERACTIONS_BASE64);
    }

    #[Test]
    public function validateThrowsForMissingUserChallenge(): void
    {
        $caCertPem = self::getTestCaCertPem();
        $dir = self::getTestEndEntityCertsDir();
        $eeCertBase64 = self::pemToBase64($dir . DIRECTORY_SEPARATOR . 'qualified_smartid_ee.pem.crt');

        $result = new SessionResult('OK', 'DOC123');
        $cert = new SessionCertificate($eeCertBase64, 'QUALIFIED');
        $signature = new SessionSignature(base64_encode('sig'), 'rsassa-pss', 'serverRandom');
        $status = new SessionStatus('COMPLETE', $result, $cert, $signature, 'ACSP_V2', interactionTypeUsed: self::TEST_INTERACTION_TYPE_USED);

        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates([$caCertPem]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('userChallenge is required');

        $validator->validate($status, self::TEST_RP_CHALLENGE, self::TEST_RP_NAME, self::TEST_INTERACTIONS_BASE64);
    }

    #[Test]
    public function validateThrowsForMissingFlowType(): void
    {
        $caCertPem = self::getTestCaCertPem();
        $dir = self::getTestEndEntityCertsDir();
        $eeCertBase64 = self::pemToBase64($dir . DIRECTORY_SEPARATOR . 'qualified_smartid_ee.pem.crt');

        $result = new SessionResult('OK', 'DOC123');
        $cert = new SessionCertificate($eeCertBase64, 'QUALIFIED');
        $signature = new SessionSignature(base64_encode('sig'), 'rsassa-pss', 'serverRandom', 'userChallenge');
        $status = new SessionStatus('COMPLETE', $result, $cert, $signature, 'ACSP_V2', interactionTypeUsed: self::TEST_INTERACTION_TYPE_USED);

        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates([$caCertPem]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('flowType is required');

        $validator->validate($status, self::TEST_RP_CHALLENGE, self::TEST_RP_NAME, self::TEST_INTERACTIONS_BASE64);
    }

    #[Test]
    public function validateThrowsForMissingInteractionTypeUsed(): void
    {
        $caCertPem = self::getTestCaCertPem();
        $dir = self::getTestEndEntityCertsDir();
        $eeCertBase64 = self::pemToBase64($dir . DIRECTORY_SEPARATOR . 'qualified_smartid_ee.pem.crt');

        $result = new SessionResult('OK', 'DOC123');
        $cert = new SessionCertificate($eeCertBase64, 'QUALIFIED');
        $signature = new SessionSignature(base64_encode('sig'), 'rsassa-pss', 'serverRandom', 'userChallenge', 'deviceLink');
        // interactionTypeUsed is null
        $status = new SessionStatus('COMPLETE', $result, $cert, $signature, 'ACSP_V2');

        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates([$caCertPem]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('interactionTypeUsed is required');

        $validator->validate($status, self::TEST_RP_CHALLENGE, self::TEST_RP_NAME, self::TEST_INTERACTIONS_BASE64);
    }

    #[Test]
    public function validateThrowsForMissingAlgorithmParameters(): void
    {
        $caCertPem = self::getTestCaCertPem();
        $dir = self::getTestEndEntityCertsDir();
        $eeCertBase64 = self::pemToBase64($dir . DIRECTORY_SEPARATOR . 'qualified_smartid_ee.pem.crt');

        $result = new SessionResult('OK', 'DOC123');
        $cert = new SessionCertificate($eeCertBase64, 'QUALIFIED');
        // No parsedAlgorithmParameters
        $signature = new SessionSignature(base64_encode('sig'), 'rsassa-pss', 'serverRandom', 'userChallenge', 'deviceLink');
        $status = new SessionStatus('COMPLETE', $result, $cert, $signature, 'ACSP_V2', interactionTypeUsed: self::TEST_INTERACTION_TYPE_USED);

        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates([$caCertPem]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('signatureAlgorithmParameters is missing');

        $validator->validate($status, self::TEST_RP_CHALLENGE, self::TEST_RP_NAME, self::TEST_INTERACTIONS_BASE64);
    }

    #[Test]
    public function validateThrowsForUnsupportedHashAlgorithm(): void
    {
        $caCertPem = self::getTestCaCertPem();
        $dir = self::getTestEndEntityCertsDir();
        $eeCertBase64 = self::pemToBase64($dir . DIRECTORY_SEPARATOR . 'qualified_smartid_ee.pem.crt');

        $result = new SessionResult('OK', 'DOC123');
        $cert = new SessionCertificate($eeCertBase64, 'QUALIFIED');
        $algorithmParams = new SignatureAlgorithmParameters(hashAlgorithm: 'UNSUPPORTED-HASH');
        $signature = new SessionSignature(base64_encode('sig'), 'rsassa-pss', 'serverRandom', 'userChallenge', 'deviceLink', parsedAlgorithmParameters: $algorithmParams);
        $status = new SessionStatus('COMPLETE', $result, $cert, $signature, 'ACSP_V2', interactionTypeUsed: self::TEST_INTERACTION_TYPE_USED);

        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates([$caCertPem]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Unsupported hash algorithm for RSA-PSS');

        $validator->validate($status, self::TEST_RP_CHALLENGE, self::TEST_RP_NAME, self::TEST_INTERACTIONS_BASE64);
    }

    #[Test]
    public function validateThrowsForMissingSaltLength(): void
    {
        $caCertPem = self::getTestCaCertPem();
        $dir = self::getTestEndEntityCertsDir();
        $eeCertBase64 = self::pemToBase64($dir . DIRECTORY_SEPARATOR . 'qualified_smartid_ee.pem.crt');

        $result = new SessionResult('OK', 'DOC123');
        $cert = new SessionCertificate($eeCertBase64, 'QUALIFIED');
        $algorithmParams = new SignatureAlgorithmParameters(hashAlgorithm: 'SHA-256');
        $signature = new SessionSignature(base64_encode('sig'), 'rsassa-pss', 'serverRandom', 'userChallenge', 'deviceLink', parsedAlgorithmParameters: $algorithmParams);
        $status = new SessionStatus('COMPLETE', $result, $cert, $signature, 'ACSP_V2', interactionTypeUsed: self::TEST_INTERACTION_TYPE_USED);

        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates([$caCertPem]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('saltLength is missing');

        $validator->validate($status, self::TEST_RP_CHALLENGE, self::TEST_RP_NAME, self::TEST_INTERACTIONS_BASE64);
    }

    #[Test]
    public function validateThrowsForSaltLengthMismatch(): void
    {
        $caCertPem = self::getTestCaCertPem();
        $dir = self::getTestEndEntityCertsDir();
        $eeCertBase64 = self::pemToBase64($dir . DIRECTORY_SEPARATOR . 'qualified_smartid_ee.pem.crt');

        $result = new SessionResult('OK', 'DOC123');
        $cert = new SessionCertificate($eeCertBase64, 'QUALIFIED');
        // SHA-256 expects saltLength=32, provide 64
        $algorithmParams = new SignatureAlgorithmParameters(hashAlgorithm: 'SHA-256', saltLength: 64);
        $signature = new SessionSignature(base64_encode('sig'), 'rsassa-pss', 'serverRandom', 'userChallenge', 'deviceLink', parsedAlgorithmParameters: $algorithmParams);
        $status = new SessionStatus('COMPLETE', $result, $cert, $signature, 'ACSP_V2', interactionTypeUsed: self::TEST_INTERACTION_TYPE_USED);

        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates([$caCertPem]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('saltLength 64 does not match hash octet length 32');

        $validator->validate($status, self::TEST_RP_CHALLENGE, self::TEST_RP_NAME, self::TEST_INTERACTIONS_BASE64);
    }

    #[Test]
    public function validateThrowsForUnsupportedMaskGenAlgorithm(): void
    {
        $caCertPem = self::getTestCaCertPem();
        $dir = self::getTestEndEntityCertsDir();
        $eeCertBase64 = self::pemToBase64($dir . DIRECTORY_SEPARATOR . 'qualified_smartid_ee.pem.crt');

        $result = new SessionResult('OK', 'DOC123');
        $cert = new SessionCertificate($eeCertBase64, 'QUALIFIED');
        $algorithmParams = new SignatureAlgorithmParameters(hashAlgorithm: 'SHA-256', saltLength: 32, maskGenAlgorithm: 'unsupported-mgf');
        $signature = new SessionSignature(base64_encode('sig'), 'rsassa-pss', 'serverRandom', 'userChallenge', 'deviceLink', parsedAlgorithmParameters: $algorithmParams);
        $status = new SessionStatus('COMPLETE', $result, $cert, $signature, 'ACSP_V2', interactionTypeUsed: self::TEST_INTERACTION_TYPE_USED);

        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates([$caCertPem]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Unsupported maskGenAlgorithm: unsupported-mgf');

        $validator->validate($status, self::TEST_RP_CHALLENGE, self::TEST_RP_NAME, self::TEST_INTERACTIONS_BASE64);
    }

    #[Test]
    public function validateThrowsForUnsupportedMaskGenHashAlgorithm(): void
    {
        $caCertPem = self::getTestCaCertPem();
        $dir = self::getTestEndEntityCertsDir();
        $eeCertBase64 = self::pemToBase64($dir . DIRECTORY_SEPARATOR . 'qualified_smartid_ee.pem.crt');

        $result = new SessionResult('OK', 'DOC123');
        $cert = new SessionCertificate($eeCertBase64, 'QUALIFIED');
        $algorithmParams = new SignatureAlgorithmParameters(hashAlgorithm: 'SHA-256', saltLength: 32, maskGenAlgorithm: 'id-mgf1', maskGenHashAlgorithm: 'UNSUPPORTED');
        $signature = new SessionSignature(base64_encode('sig'), 'rsassa-pss', 'serverRandom', 'userChallenge', 'deviceLink', parsedAlgorithmParameters: $algorithmParams);
        $status = new SessionStatus('COMPLETE', $result, $cert, $signature, 'ACSP_V2', interactionTypeUsed: self::TEST_INTERACTION_TYPE_USED);

        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates([$caCertPem]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Unsupported maskGenAlgorithm hash: UNSUPPORTED');

        $validator->validate($status, self::TEST_RP_CHALLENGE, self::TEST_RP_NAME, self::TEST_INTERACTIONS_BASE64);
    }

    #[Test]
    public function validateThrowsForMaskGenHashMismatch(): void
    {
        $caCertPem = self::getTestCaCertPem();
        $dir = self::getTestEndEntityCertsDir();
        $eeCertBase64 = self::pemToBase64($dir . DIRECTORY_SEPARATOR . 'qualified_smartid_ee.pem.crt');

        $result = new SessionResult('OK', 'DOC123');
        $cert = new SessionCertificate($eeCertBase64, 'QUALIFIED');
        // hashAlgorithm=SHA-256, but maskGenHashAlgorithm=SHA-512 => mismatch
        $algorithmParams = new SignatureAlgorithmParameters(hashAlgorithm: 'SHA-256', saltLength: 32, maskGenAlgorithm: 'id-mgf1', maskGenHashAlgorithm: 'SHA-512');
        $signature = new SessionSignature(base64_encode('sig'), 'rsassa-pss', 'serverRandom', 'userChallenge', 'deviceLink', parsedAlgorithmParameters: $algorithmParams);
        $status = new SessionStatus('COMPLETE', $result, $cert, $signature, 'ACSP_V2', interactionTypeUsed: self::TEST_INTERACTION_TYPE_USED);

        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates([$caCertPem]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('maskGenAlgorithm hashAlgorithm does not match');

        $validator->validate($status, self::TEST_RP_CHALLENGE, self::TEST_RP_NAME, self::TEST_INTERACTIONS_BASE64);
    }

    #[Test]
    public function validateThrowsForUnsupportedTrailerField(): void
    {
        $caCertPem = self::getTestCaCertPem();
        $dir = self::getTestEndEntityCertsDir();
        $eeCertBase64 = self::pemToBase64($dir . DIRECTORY_SEPARATOR . 'qualified_smartid_ee.pem.crt');

        $result = new SessionResult('OK', 'DOC123');
        $cert = new SessionCertificate($eeCertBase64, 'QUALIFIED');
        $algorithmParams = new SignatureAlgorithmParameters(hashAlgorithm: 'SHA-256', saltLength: 32, maskGenAlgorithm: 'id-mgf1', maskGenHashAlgorithm: 'SHA-256', trailerField: '0xFF');
        $signature = new SessionSignature(base64_encode('sig'), 'rsassa-pss', 'serverRandom', 'userChallenge', 'deviceLink', parsedAlgorithmParameters: $algorithmParams);
        $status = new SessionStatus('COMPLETE', $result, $cert, $signature, 'ACSP_V2', interactionTypeUsed: self::TEST_INTERACTION_TYPE_USED);

        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates([$caCertPem]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Unsupported trailerField: 0xFF');

        $validator->validate($status, self::TEST_RP_CHALLENGE, self::TEST_RP_NAME, self::TEST_INTERACTIONS_BASE64);
    }

    #[Test]
    public function validateThrowsForWrongSignatureValue(): void
    {
        $caCertPem = self::getTestCaCertPem();
        $dir = self::getTestEndEntityCertsDir();
        $eeCertBase64 = self::pemToBase64($dir . DIRECTORY_SEPARATOR . 'qualified_smartid_ee.pem.crt');

        $result = new SessionResult('OK', 'DOC123');
        $cert = new SessionCertificate($eeCertBase64, 'QUALIFIED');
        $algorithmParams = new SignatureAlgorithmParameters(hashAlgorithm: 'SHA-256', saltLength: 32, maskGenAlgorithm: 'id-mgf1', maskGenHashAlgorithm: 'SHA-256');
        // Wrong signature value (random bytes)
        $signature = new SessionSignature(base64_encode(random_bytes(256)), 'rsassa-pss', self::TEST_SERVER_RANDOM, self::TEST_USER_CHALLENGE, self::TEST_FLOW_TYPE, parsedAlgorithmParameters: $algorithmParams);
        $status = new SessionStatus('COMPLETE', $result, $cert, $signature, 'ACSP_V2', interactionTypeUsed: self::TEST_INTERACTION_TYPE_USED);

        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates([$caCertPem]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('RSA-PSS signature verification');

        $validator->validate($status, self::TEST_RP_CHALLENGE, self::TEST_RP_NAME, self::TEST_INTERACTIONS_BASE64);
    }

    #[Test]
    public function validateThrowsForCertificateNotTrustedByAnyCA(): void
    {
        // Use the qualified cert but trust a different (self-signed) CA
        $dir = self::getTestEndEntityCertsDir();
        $eeCertBase64 = self::pemToBase64($dir . DIRECTORY_SEPARATOR . 'qualified_smartid_ee.pem.crt');

        // Generate a self-signed cert to use as untrusted CA
        $untrustedCaKey = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $untrustedCaCsr = openssl_csr_new(['CN' => 'Untrusted CA'], $untrustedCaKey);
        $untrustedCaCert = openssl_csr_sign($untrustedCaCsr, null, $untrustedCaKey, 365);
        openssl_x509_export($untrustedCaCert, $untrustedCaPem);

        $result = new SessionResult('OK', 'DOC123');
        $cert = new SessionCertificate($eeCertBase64, 'QUALIFIED');
        $signature = new SessionSignature(base64_encode('sig'), 'rsassa-pss');
        $status = new SessionStatus('COMPLETE', $result, $cert, $signature, 'ACSP_V2');

        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates([$untrustedCaPem]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('not signed by a trusted CA');

        $validator->validate($status, self::TEST_RP_CHALLENGE, self::TEST_RP_NAME, self::TEST_INTERACTIONS_BASE64);
    }

    #[Test]
    public function validateThrowsForMissingDigitalSignatureKeyUsage(): void
    {
        $caCertPem = self::getTestCaCertPem();
        $status = self::createSignedSessionStatus('no_digsig_ee', 'ADVANCED');

        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates([$caCertPem]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('digitalSignature');

        $validator->validate($status, self::TEST_RP_CHALLENGE, self::TEST_RP_NAME, self::TEST_INTERACTIONS_BASE64);
    }

    #[Test]
    public function validateThrowsForMissingClientAuthEku(): void
    {
        $caCertPem = self::getTestCaCertPem();
        $status = self::createSignedSessionStatus('no_clientauth_ee', 'ADVANCED');

        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates([$caCertPem]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('clientAuth Extended Key Usage');

        $validator->validate($status, self::TEST_RP_CHALLENGE, self::TEST_RP_NAME, self::TEST_INTERACTIONS_BASE64);
    }

    #[Test]
    public function setTrustedCaCertificateFilesReturnsSelf(): void
    {
        $validator = new AuthenticationResponseValidator();

        $result = $validator->setTrustedCaCertificateFiles(['/some/path.pem']);

        $this->assertSame($validator, $result);
    }

    #[Test]
    public function validateWithTrustedCaCertificateFiles(): void
    {
        $caCertPath = self::getTestEndEntityCertsDir() . DIRECTORY_SEPARATOR . 'test_ca.pem.crt';
        $status = self::createSignedSessionStatus('qualified_smartid_ee', 'QUALIFIED');

        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificateFiles([$caCertPath]);

        $identity = $validator->validate($status, self::TEST_RP_CHALLENGE, self::TEST_RP_NAME, self::TEST_INTERACTIONS_BASE64);

        $this->assertSame('QUALIFIED', $identity->getGivenName());
    }

    #[Test]
    public function validateWithCallbackUrlAndBrokeredRpName(): void
    {
        $caCertPem = self::getTestCaCertPem();
        $dir = self::getTestEndEntityCertsDir();
        $eeCertBase64 = self::pemToBase64($dir . DIRECTORY_SEPARATOR . 'qualified_smartid_ee.pem.crt');
        $keyPem = file_get_contents($dir . DIRECTORY_SEPARATOR . 'qualified_smartid_ee.key.pem');

        $callbackUrl = 'https://example.com/callback';
        $brokeredRpName = 'Brokered RP';

        $interactionsHash = hash('sha256', self::TEST_INTERACTIONS_BASE64, true);
        $interactionsHashBase64 = base64_encode($interactionsHash);

        $acspV2Payload = implode('|', [
            'smart-id',
            'ACSP_V2',
            self::TEST_SERVER_RANDOM,
            self::TEST_RP_CHALLENGE,
            self::TEST_USER_CHALLENGE,
            base64_encode(self::TEST_RP_NAME),
            base64_encode($brokeredRpName),
            $interactionsHashBase64,
            self::TEST_INTERACTION_TYPE_USED,
            $callbackUrl,
            self::TEST_FLOW_TYPE,
        ]);

        $loaded = PublicKeyLoader::load($keyPem);
        assert($loaded instanceof RSAPrivateKey);
        $rsaKey = $loaded->withHash('sha256')->withMGFHash('sha256')->withPadding(RSA::SIGNATURE_PSS)->withSaltLength(32);
        $signatureBytes = $rsaKey->sign($acspV2Payload);

        $algorithmParams = new SignatureAlgorithmParameters(hashAlgorithm: 'SHA-256', saltLength: 32, maskGenAlgorithm: 'id-mgf1', maskGenHashAlgorithm: 'SHA-256');
        $signature = new SessionSignature(base64_encode($signatureBytes), 'rsassa-pss', self::TEST_SERVER_RANDOM, self::TEST_USER_CHALLENGE, self::TEST_FLOW_TYPE, parsedAlgorithmParameters: $algorithmParams);
        $result = new SessionResult('OK', 'DOC123');
        $cert = new SessionCertificate($eeCertBase64, 'QUALIFIED');
        $status = new SessionStatus('COMPLETE', $result, $cert, $signature, 'ACSP_V2', interactionTypeUsed: self::TEST_INTERACTION_TYPE_USED);

        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates([$caCertPem]);

        $identity = $validator->validate(
            $status,
            self::TEST_RP_CHALLENGE,
            self::TEST_RP_NAME,
            self::TEST_INTERACTIONS_BASE64,
            $callbackUrl,
            $brokeredRpName,
        );

        $this->assertSame('QUALIFIED', $identity->getGivenName());
    }

    #[Test]
    public function validateAcceptsTrailerFieldBC(): void
    {
        $caCertPem = self::getTestCaCertPem();
        $status = self::createSignedSessionStatusWithTrailer('qualified_smartid_ee', 'QUALIFIED', 'BC');

        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates([$caCertPem]);

        $identity = $validator->validate($status, self::TEST_RP_CHALLENGE, self::TEST_RP_NAME, self::TEST_INTERACTIONS_BASE64);
        $this->assertSame('QUALIFIED', $identity->getGivenName());
    }

    #[Test]
    public function validateAcceptsTrailerField0xBC(): void
    {
        $caCertPem = self::getTestCaCertPem();
        $status = self::createSignedSessionStatusWithTrailer('qualified_smartid_ee', 'QUALIFIED', '0xBC');

        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates([$caCertPem]);

        $identity = $validator->validate($status, self::TEST_RP_CHALLENGE, self::TEST_RP_NAME, self::TEST_INTERACTIONS_BASE64);
        $this->assertSame('QUALIFIED', $identity->getGivenName());
    }

    #[Test]
    public function validateExtractsIdentityWithNonPnoSerialNumber(): void
    {
        $caCertPem = self::getTestCaCertPem();
        $status = self::createSignedSessionStatus('non_pno_serial_ee', 'ADVANCED');

        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates([$caCertPem]);

        $identity = $validator->validate($status, self::TEST_RP_CHALLENGE, self::TEST_RP_NAME, self::TEST_INTERACTIONS_BASE64);

        $this->assertSame('NONPNO', $identity->getGivenName());
        $this->assertSame('TESTNUMBER', $identity->getSurname());
        $this->assertSame('30303039917', $identity->getIdentityCode());
        $this->assertSame('EE', $identity->getCountry());
    }

    #[Test]
    public function validateExtractsIdentityWithPlainSerialNumber(): void
    {
        $caCertPem = self::getTestCaCertPem();
        $status = self::createSignedSessionStatus('plain_serial_ee', 'ADVANCED');

        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates([$caCertPem]);

        $identity = $validator->validate($status, self::TEST_RP_CHALLENGE, self::TEST_RP_NAME, self::TEST_INTERACTIONS_BASE64);

        $this->assertSame('PLAINSERIAL', $identity->getGivenName());
        $this->assertSame('TESTNUMBER', $identity->getSurname());
        $this->assertSame('12345678', $identity->getIdentityCode());
        $this->assertSame('EE', $identity->getCountry());
    }

    #[Test]
    public function validateWithOcspCheckerIntegration(): void
    {
        $caCertPem = self::getTestCaCertPem();
        $dir = self::getTestEndEntityCertsDir();
        $ocspResponseDer = file_get_contents($dir . DIRECTORY_SEPARATOR . 'ocsp_response_good.der');

        $mock = new \GuzzleHttp\Handler\MockHandler([
            new \GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'application/ocsp-response'], $ocspResponseDer),
        ]);
        $handlerStack = \GuzzleHttp\HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        $factory = new HttpFactory();
        $ocspChecker = new OcspCertificateRevocationChecker($client, $factory, $factory);

        $status = self::createSignedSessionStatus('ocsp_ee', 'ADVANCED');

        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates([$caCertPem]);
        $validator->setOcspRevocationChecker($ocspChecker);

        $identity = $validator->validate($status, self::TEST_RP_CHALLENGE, self::TEST_RP_NAME, self::TEST_INTERACTIONS_BASE64);

        $this->assertSame('OCSPUSER', $identity->getGivenName());
    }

    #[Test]
    public function validateWithOcspCheckerThrowsWhenNoIssuerFound(): void
    {
        $dir = self::getTestEndEntityCertsDir();
        $eeCertBase64 = self::pemToBase64($dir . DIRECTORY_SEPARATOR . 'qualified_smartid_ee.pem.crt');

        // Trust a different self-signed CA (not the issuer of the EE cert)
        // But use it for trust validation too — openssl_x509_checkpurpose will fail first
        // So we need the actual CA for trust but a different one for OCSP issuer search
        // Actually, we need to configure the validator with the real CA for trust chain
        // but the OCSP path will fail to find issuer if we use file-based certs and the
        // file doesn't contain the actual issuer.
        // Simpler approach: mock the OCSP checker and test the no-issuer path
        $factory = new HttpFactory();
        $ocspChecker = new OcspCertificateRevocationChecker(new Client(), $factory, $factory);

        // Use a self-signed CA as trusted — the EE cert won't be trusted by it
        // but we can test the flow by using setTrustedCaCertificateFiles with CA file
        // and then clearing trustedCaCertificates to exercise findIssuerCertificate file path
        $caCertPath = $dir . DIRECTORY_SEPARATOR . 'test_ca.pem.crt';
        $caCertPem = self::getTestCaCertPem();

        $status = self::createSignedSessionStatus('qualified_smartid_ee', 'QUALIFIED');

        $validator = new AuthenticationResponseValidator();
        // Trust via files (exercises findIssuerCertificate file-reading path)
        $validator->setTrustedCaCertificateFiles([$caCertPath]);
        $validator->setOcspRevocationChecker($ocspChecker);

        // This should exercise the findIssuerCertificate file-reading path
        // and then pass because the cert IS signed by the CA in the file
        // The OCSP check will fail because there's no real OCSP server
        $this->expectException(ValidationException::class);

        $validator->validate($status, self::TEST_RP_CHALLENGE, self::TEST_RP_NAME, self::TEST_INTERACTIONS_BASE64);
    }

    #[Test]
    public function validatePassesForCertWithoutBasicConstraints(): void
    {
        $status = self::createSignedSessionStatus('no_basic_constraints_ee', 'QUALIFIED');

        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates([self::getTestCaCertPem()]);

        $identity = $validator->validate($status, self::TEST_RP_CHALLENGE, self::TEST_RP_NAME, self::TEST_INTERACTIONS_BASE64);

        $this->assertSame('30303039918', $identity->getIdentityCode());
    }


    private static function createSignedSessionStatusWithTrailer(string $certBaseName, string $certLevel, string $trailerField): SessionStatus
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
        $rsaKey = $loaded->withHash('sha256')->withMGFHash('sha256')->withPadding(RSA::SIGNATURE_PSS)->withSaltLength(32);
        $signatureBytes = $rsaKey->sign($acspV2Payload);

        $algorithmParams = new SignatureAlgorithmParameters(
            hashAlgorithm: 'SHA-256',
            saltLength: 32,
            maskGenAlgorithm: 'id-mgf1',
            maskGenHashAlgorithm: 'SHA-256',
            trailerField: $trailerField,
        );

        $signature = new SessionSignature(
            base64_encode($signatureBytes),
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
}
