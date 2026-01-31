<?php

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
