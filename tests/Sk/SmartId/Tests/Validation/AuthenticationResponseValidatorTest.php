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

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\Enum\CertificateLevel;
use Sk\SmartId\Exception\ValidationException;
use Sk\SmartId\Session\SessionCertificate;
use Sk\SmartId\Session\SessionResult;
use Sk\SmartId\Session\SessionSignature;
use Sk\SmartId\Session\SessionStatus;
use Sk\SmartId\Validation\AuthenticationResponseValidator;
use Sk\SmartId\Validation\OcspCertificateRevocationChecker;

class AuthenticationResponseValidatorTest extends TestCase
{
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
            $status, base64_encode('challenge'), 'DEMO', 'aW50ZXJhY3Rpb25z',
            null, null, CertificateLevel::QUALIFIED,
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
            $status, base64_encode('challenge'), 'DEMO', 'aW50ZXJhY3Rpb25z',
            null, null, CertificateLevel::QUALIFIED,
        );
    }

    #[Test]
    public function validatePassesCertificatePoliciesForQualifiedSmartId(): void
    {
        $caCertPem = self::getTestCaCertPem();
        $eeCertBase64 = self::pemToBase64(self::getTestEndEntityCertsDir() . DIRECTORY_SEPARATOR . 'qualified_smartid_ee.pem.crt');

        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates([$caCertPem]);
        $validator->setSkipSignatureVerification(true);

        $result = new SessionResult('OK', 'DOC123');
        $cert = new SessionCertificate($eeCertBase64, 'QUALIFIED');
        $signature = new SessionSignature(base64_encode('sig'), 'rsassa-pss');
        $status = new SessionStatus('COMPLETE', $result, $cert, $signature, 'ACSP_V2');

        // Test cert has both Smart-ID qualified policy OIDs and clientAuth EKU,
        // so policies and purpose checks both pass. validate() should succeed.
        $identity = $validator->validate($status, base64_encode('challenge'), 'DEMO', 'aW50ZXJhY3Rpb25z');

        $this->assertSame('QUALIFIED', $identity->getGivenName());
        $this->assertSame('TESTNUMBER', $identity->getSurname());
        $this->assertSame('30303039914', $identity->getIdentityCode());
        $this->assertSame('EE', $identity->getCountry());
    }

    #[Test]
    public function validatePassesCertificatePoliciesForNonQualifiedSmartId(): void
    {
        $caCertPem = self::getTestCaCertPem();
        $eeCertBase64 = self::pemToBase64(self::getTestEndEntityCertsDir() . DIRECTORY_SEPARATOR . 'non_qualified_smartid_ee.pem.crt');

        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates([$caCertPem]);
        $validator->setSkipSignatureVerification(true);

        $result = new SessionResult('OK', 'DOC123');
        $cert = new SessionCertificate($eeCertBase64, 'ADVANCED');
        $signature = new SessionSignature(base64_encode('sig'), 'rsassa-pss');
        $status = new SessionStatus('COMPLETE', $result, $cert, $signature, 'ACSP_V2');

        // Test cert has non-qualified Smart-ID policy OID and clientAuth EKU,
        // so policies and purpose checks both pass. validate() should succeed.
        $identity = $validator->validate($status, base64_encode('challenge'), 'DEMO', 'aW50ZXJhY3Rpb25z');

        $this->assertSame('NONQUALIFIED', $identity->getGivenName());
        $this->assertSame('TESTNUMBER', $identity->getSurname());
        $this->assertSame('30303039915', $identity->getIdentityCode());
        $this->assertSame('EE', $identity->getCountry());
    }

    #[Test]
    public function validateThrowsForNonSmartIdCertificatePolicies(): void
    {
        $caCertPem = self::getTestCaCertPem();
        $eeCertBase64 = self::pemToBase64(self::getTestEndEntityCertsDir() . DIRECTORY_SEPARATOR . 'non_smartid_ee.pem.crt');

        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates([$caCertPem]);
        $validator->setSkipSignatureVerification(true);

        $result = new SessionResult('OK', 'DOC123');
        $cert = new SessionCertificate($eeCertBase64, 'QUALIFIED');
        $signature = new SessionSignature(base64_encode('sig'), 'rsassa-pss');
        $status = new SessionStatus('COMPLETE', $result, $cert, $signature, 'ACSP_V2');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Smart-ID scheme Certificate Policy OIDs');

        $validator->validate($status, base64_encode('challenge'), 'DEMO', 'aW50ZXJhY3Rpb25z');
    }

    #[Test]
    public function validateThrowsForCertificateWithNoPolicies(): void
    {
        $caCertPem = self::getTestCaCertPem();
        $eeCertBase64 = self::pemToBase64(self::getTestEndEntityCertsDir() . DIRECTORY_SEPARATOR . 'no_policies_ee.pem.crt');

        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates([$caCertPem]);
        $validator->setSkipSignatureVerification(true);

        $result = new SessionResult('OK', 'DOC123');
        $cert = new SessionCertificate($eeCertBase64, 'QUALIFIED');
        $signature = new SessionSignature(base64_encode('sig'), 'rsassa-pss');
        $status = new SessionStatus('COMPLETE', $result, $cert, $signature, 'ACSP_V2');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Smart-ID scheme Certificate Policy OIDs');

        $validator->validate($status, base64_encode('challenge'), 'DEMO', 'aW50ZXJhY3Rpb25z');
    }

    #[Test]
    public function validateThrowsForQualifiedCertMissingEuQcpPolicy(): void
    {
        $caCertPem = self::getTestCaCertPem();
        $eeCertBase64 = self::pemToBase64(self::getTestEndEntityCertsDir() . DIRECTORY_SEPARATOR . 'qualified_missing_euqcp_ee.pem.crt');

        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates([$caCertPem]);
        $validator->setSkipSignatureVerification(true);

        $result = new SessionResult('OK', 'DOC123');
        $cert = new SessionCertificate($eeCertBase64, 'QUALIFIED');
        $signature = new SessionSignature(base64_encode('sig'), 'rsassa-pss');
        $status = new SessionStatus('COMPLETE', $result, $cert, $signature, 'ACSP_V2');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('EU QCP policy OID 0.4.0.2042.1.2');

        $validator->validate($status, base64_encode('challenge'), 'DEMO', 'aW50ZXJhY3Rpb25z');
    }

    #[Test]
    public function setOcspRevocationCheckerThrowsRuntimeException(): void
    {
        $validator = new AuthenticationResponseValidator();
        $checker = new OcspCertificateRevocationChecker();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('OCSP revocation checking is not yet available');

        $validator->setOcspRevocationChecker($checker);
    }

    #[Test]
    public function setOcspRevocationCheckerWithNullThrowsRuntimeException(): void
    {
        $validator = new AuthenticationResponseValidator();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('OCSP revocation checking is not yet available');

        $validator->setOcspRevocationChecker(null);
    }

    #[Test]
    public function validateThrowsForCaCertificateUsedAsEndEntity(): void
    {
        // Use the test CA cert (has CA:TRUE) as if it were an end-entity cert
        $caCertPem = self::getTestCaCertPem();
        $caCertBase64 = self::pemToBase64(self::getTestEndEntityCertsDir() . DIRECTORY_SEPARATOR . 'test_ca.pem.crt');

        $validator = new AuthenticationResponseValidator();
        // Trust itself (self-signed)
        $validator->setTrustedCaCertificates([$caCertPem]);
        $validator->setSkipSignatureVerification(true);

        $result = new SessionResult('OK', 'DOC123');
        $cert = new SessionCertificate($caCertBase64, 'QUALIFIED');
        $signature = new SessionSignature(base64_encode('sig'), 'rsassa-pss');
        $status = new SessionStatus('COMPLETE', $result, $cert, $signature, 'ACSP_V2');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('CA:TRUE');

        $validator->validate($status, base64_encode('challenge'), 'DEMO', 'aW50ZXJhY3Rpb25z');
    }

    #[Test]
    public function validatePassesBasicConstraintsForEndEntityCert(): void
    {
        $caCertPem = self::getTestCaCertPem();
        $eeCertBase64 = self::pemToBase64(self::getTestEndEntityCertsDir() . DIRECTORY_SEPARATOR . 'qualified_smartid_ee.pem.crt');

        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates([$caCertPem]);
        $validator->setSkipSignatureVerification(true);

        $result = new SessionResult('OK', 'DOC123');
        $cert = new SessionCertificate($eeCertBase64, 'QUALIFIED');
        $signature = new SessionSignature(base64_encode('sig'), 'rsassa-pss');
        $status = new SessionStatus('COMPLETE', $result, $cert, $signature, 'ACSP_V2');

        // EE cert has CA:FALSE — should pass basic constraints and all other checks
        $identity = $validator->validate($status, base64_encode('challenge'), 'DEMO', 'aW50ZXJhY3Rpb25z');

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
