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

    #[Test]
    public function fromArrayThrowsForMissingSessionId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('sessionID');

        DeviceLinkAuthenticationResponse::fromArray([
            'sessionToken' => 'token',
            'sessionSecret' => 'secret',
            'deviceLinkBase' => 'https://example.com',
        ]);
    }

    #[Test]
    public function fromArrayThrowsForNonStringFields(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must be strings');

        DeviceLinkAuthenticationResponse::fromArray([
            'sessionID' => 123,
            'sessionToken' => 'token',
            'sessionSecret' => 'secret',
            'deviceLinkBase' => 'https://example.com',
        ]);
    }
}
