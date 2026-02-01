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

    #[Test]
    public function validateThrowsWhenCertificateLevelDoesNotMeetRequirement(): void
    {
        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates(['dummy-cert']);
        $result = new SessionResult('OK', 'DOC123');
        $cert = new SessionCertificate('certValue', 'ADVANCED');
        $signature = new SessionSignature(base64_encode('sig'), 'SHA512WithRSA');
        $status = new SessionStatus('COMPLETE', $result, $cert, $signature);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Certificate level ADVANCED does not meet required level QUALIFIED');

        $validator->validate($status, base64_encode('challenge'), CertificateLevel::QUALIFIED);
    }

    #[Test]
    public function validateThrowsForUnknownCertificateLevel(): void
    {
        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates(['dummy-cert']);
        $result = new SessionResult('OK', 'DOC123');
        $cert = new SessionCertificate('certValue', 'UNKNOWN_LEVEL');
        $signature = new SessionSignature(base64_encode('sig'), 'SHA512WithRSA');
        $status = new SessionStatus('COMPLETE', $result, $cert, $signature);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Unknown certificate level: UNKNOWN_LEVEL');

        $validator->validate($status, base64_encode('challenge'), CertificateLevel::QUALIFIED);
    }
}
