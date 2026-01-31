<?php

declare(strict_types=1);

namespace Sk\SmartId\Tests\DeviceLink;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\DeviceLink\DeviceLinkAuthenticationResponse;

class DeviceLinkAuthenticationResponseTest extends TestCase
{
    #[Test]
    public function fromArrayCreatesResponse(): void
    {
        $response = DeviceLinkAuthenticationResponse::fromArray([
            'sessionID' => 'test-session-id',
            'sessionToken' => 'test-session-token',
            'sessionSecret' => 'dGVzdC1zZWNyZXQ=',
            'deviceLinkBase' => 'https://sid.demo.sk.ee/v3/device',
        ]);

        $this->assertSame('test-session-id', $response->getSessionID());
        $this->assertSame('test-session-token', $response->getSessionToken());
        $this->assertSame('dGVzdC1zZWNyZXQ=', $response->getSessionSecret());
        $this->assertSame('https://sid.demo.sk.ee/v3/device', $response->getDeviceLinkBase());
    }

    #[Test]
    public function constructorSetsAllProperties(): void
    {
        $response = new DeviceLinkAuthenticationResponse(
            'session-123',
            'token-456',
            'secret-789',
            'https://example.com/device',
        );

        $this->assertSame('session-123', $response->getSessionID());
        $this->assertSame('token-456', $response->getSessionToken());
        $this->assertSame('secret-789', $response->getSessionSecret());
        $this->assertSame('https://example.com/device', $response->getDeviceLinkBase());
    }
}
