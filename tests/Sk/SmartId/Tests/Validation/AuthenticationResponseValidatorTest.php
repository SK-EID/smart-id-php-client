<?php

declare(strict_types=1);

namespace Sk\SmartId\Tests\Validation;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\Exception\ValidationException;
use Sk\SmartId\Session\SessionCertificate;
use Sk\SmartId\Session\SessionResult;
use Sk\SmartId\Session\SessionSignature;
use Sk\SmartId\Session\SessionStatus;
use Sk\SmartId\Validation\AuthenticationResponseValidator;

class AuthenticationResponseValidatorTest extends TestCase
{
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

        $validator->validate($status, 'rpChallenge');
    }

    #[Test]
    public function validateThrowsForNullResult(): void
    {
        $validator = new AuthenticationResponseValidator();
        $status = new SessionStatus('COMPLETE');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Session result is not OK');

        $validator->validate($status, 'rpChallenge');
    }

    #[Test]
    public function validateThrowsForNonOkResult(): void
    {
        $validator = new AuthenticationResponseValidator();
        $result = new SessionResult('USER_REFUSED');
        $status = new SessionStatus('COMPLETE', $result);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Session result is not OK');

        $validator->validate($status, 'rpChallenge');
    }

    #[Test]
    public function validateThrowsForMissingCertificate(): void
    {
        $validator = new AuthenticationResponseValidator();
        $result = new SessionResult('OK', 'DOC123');
        $status = new SessionStatus('COMPLETE', $result);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Certificate is missing from session response');

        $validator->validate($status, 'rpChallenge');
    }

    #[Test]
    public function validateThrowsForMissingSignature(): void
    {
        $validator = new AuthenticationResponseValidator();
        $result = new SessionResult('OK', 'DOC123');
        $cert = new SessionCertificate('certValue', 'QUALIFIED');
        $status = new SessionStatus('COMPLETE', $result, $cert);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Signature is missing from session response');

        $validator->validate($status, 'rpChallenge');
    }

    #[Test]
    public function validateThrowsWhenNoTrustedCaCertificatesConfigured(): void
    {
        $validator = new AuthenticationResponseValidator();
        $result = new SessionResult('OK', 'DOC123');
        $cert = new SessionCertificate('certValue', 'QUALIFIED');
        $signature = new SessionSignature(base64_encode('sig'), 'SHA512WithRSA');
        $status = new SessionStatus('COMPLETE', $result, $cert, $signature);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('No trusted CA certificates configured');

        $validator->validate($status, base64_encode('challenge'));
    }
}
