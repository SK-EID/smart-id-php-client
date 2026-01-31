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

namespace Sk\SmartId\Tests\Session;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\Session\SessionCertificate;

class SessionCertificateTest extends TestCase
{
    #[Test]
    public function constructorSetsProperties(): void
    {
        $cert = new SessionCertificate('certValue', 'QUALIFIED');

        $this->assertSame('certValue', $cert->getValue());
        $this->assertSame('QUALIFIED', $cert->getCertificateLevel());
    }

    #[Test]
    public function fromArrayCreatesCertificate(): void
    {
        $data = [
            'value' => 'base64CertValue',
            'certificateLevel' => 'ADVANCED',
        ];

        $cert = SessionCertificate::fromArray($data);

        $this->assertSame('base64CertValue', $cert->getValue());
        $this->assertSame('ADVANCED', $cert->getCertificateLevel());
    }

    #[Test]
    public function getPemEncodedCertificateReturnsFormattedPem(): void
    {
        $certValue = 'MIIBkTCB+wIJAKHBfpEgcMFvMA0GCSqGSIb3DQEBCwUAMBExDzANBgNVBAMMBnRl';
        $cert = new SessionCertificate($certValue, 'QUALIFIED');

        $pem = $cert->getPemEncodedCertificate();

        $this->assertStringStartsWith("-----BEGIN CERTIFICATE-----\n", $pem);
        $this->assertStringEndsWith("-----END CERTIFICATE-----\n", $pem);
        $this->assertStringContainsString($certValue, str_replace("\n", '', $pem));
    }
}
