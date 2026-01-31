<?php

declare(strict_types=1);

namespace Sk\SmartId\Tests\Session;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\Session\SessionCertificate;
use Sk\SmartId\Session\SessionResult;
use Sk\SmartId\Session\SessionSignature;
use Sk\SmartId\Session\SessionStatus;

class SessionStatusTest extends TestCase
{
    #[Test]
    public function fromArrayCreatesRunningStatus(): void
    {
        $status = SessionStatus::fromArray([
            'state' => 'RUNNING',
        ]);

        $this->assertSame('RUNNING', $status->getState());
        $this->assertTrue($status->isRunning());
        $this->assertFalse($status->isComplete());
        $this->assertNull($status->getResult());
    }

    #[Test]
    public function fromArrayCreatesCompleteStatusWithResult(): void
    {
        $status = SessionStatus::fromArray([
            'state' => 'COMPLETE',
            'result' => [
                'endResult' => 'OK',
                'documentNumber' => 'PNOEE-12345678901-MOCK-Q',
            ],
        ]);

        $this->assertSame('COMPLETE', $status->getState());
        $this->assertFalse($status->isRunning());
        $this->assertTrue($status->isComplete());
        $this->assertNotNull($status->getResult());
        $this->assertSame('OK', $status->getResult()->getEndResult());
    }

    #[Test]
    public function constructorWithResult(): void
    {
        $result = new SessionResult('OK', 'PNOEE-12345678901');
        $status = new SessionStatus('COMPLETE', $result);

        $this->assertSame('COMPLETE', $status->getState());
        $this->assertSame($result, $status->getResult());
    }

    #[Test]
    public function constructorWithoutResult(): void
    {
        $status = new SessionStatus('RUNNING');

        $this->assertSame('RUNNING', $status->getState());
        $this->assertNull($status->getResult());
    }

    #[Test]
    public function stateConstantsHaveCorrectValues(): void
    {
        $this->assertSame('RUNNING', SessionStatus::STATE_RUNNING);
        $this->assertSame('COMPLETE', SessionStatus::STATE_COMPLETE);
    }

    #[Test]
    public function fromArrayParsesCertAndSignature(): void
    {
        $status = SessionStatus::fromArray([
            'state' => 'COMPLETE',
            'result' => [
                'endResult' => 'OK',
            ],
            'cert' => [
                'value' => 'certValue',
                'certificateLevel' => 'QUALIFIED',
            ],
            'signature' => [
                'value' => 'signatureValue',
                'signatureAlgorithm' => 'sha512WithRSAEncryption',
            ],
            'deviceIpAddress' => '192.168.1.1',
        ]);

        $this->assertNotNull($status->getCert());
        $this->assertSame('certValue', $status->getCert()->getValue());
        $this->assertSame('QUALIFIED', $status->getCert()->getCertificateLevel());

        $this->assertNotNull($status->getSignature());
        $this->assertSame('signatureValue', $status->getSignature()->getValue());
        $this->assertSame('sha512WithRSAEncryption', $status->getSignature()->getSignatureAlgorithm());

        $this->assertSame('192.168.1.1', $status->getDeviceIpAddress());
    }

    #[Test]
    public function constructorWithCertAndSignature(): void
    {
        $result = new SessionResult('OK');
        $cert = new SessionCertificate('certValue', 'QUALIFIED');
        $signature = new SessionSignature('sigValue', 'sha512WithRSAEncryption');

        $status = new SessionStatus('COMPLETE', $result, $cert, $signature, '10.0.0.1');

        $this->assertSame($cert, $status->getCert());
        $this->assertSame($signature, $status->getSignature());
        $this->assertSame('10.0.0.1', $status->getDeviceIpAddress());
    }

    #[Test]
    public function fromArrayWithoutOptionalFields(): void
    {
        $status = SessionStatus::fromArray([
            'state' => 'RUNNING',
        ]);

        $this->assertNull($status->getCert());
        $this->assertNull($status->getSignature());
        $this->assertNull($status->getDeviceIpAddress());
    }
}
