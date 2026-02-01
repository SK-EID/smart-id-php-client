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
use Sk\SmartId\DeviceLink\DeviceLinkBuilder;
use Sk\SmartId\Enum\DeviceLinkType;
use Sk\SmartId\Model\Interaction;

class DeviceLinkBuilderTest extends TestCase
{
    private DeviceLinkAuthenticationResponse $response;

    private string $rpChallenge;

    private string $rpName;

    /** @var Interaction[] */
    private array $interactions;

    protected function setUp(): void
    {
        $this->response = new DeviceLinkAuthenticationResponse(
            'session-123',
            'token-456',
            base64_encode('test-secret-32-bytes-long-string'),
            'https://sid.demo.sk.ee/v3/device',
        );
        $this->rpChallenge = base64_encode('test-challenge-32-bytes-long!!!');
        $this->rpName = 'Test RP';
        $this->interactions = [Interaction::verificationCodeChoice()];
    }

    #[Test]
    public function buildQrCodeUrlReturnsValidUrl(): void
    {
        $builder = new DeviceLinkBuilder(
            $this->response,
            $this->rpChallenge,
            $this->rpName,
            $this->interactions,
        );

        $url = $builder->buildQrCodeUrl();

        $this->assertStringStartsWith('https://sid.demo.sk.ee/v3/device?deviceLinkType=QR&', $url);
        $this->assertStringContainsString('sessionToken=token-456', $url);
        $this->assertStringContainsString('sessionType=auth', $url);
        $this->assertStringContainsString('version=1.0', $url);
        $this->assertStringContainsString('lang=eng', $url);
        $this->assertStringContainsString('elapsedSeconds=', $url);
        $this->assertStringContainsString('authCode=', $url);
    }

    #[Test]
    public function buildWeb2AppUrlReturnsValidUrl(): void
    {
        $builder = new DeviceLinkBuilder(
            $this->response,
            $this->rpChallenge,
            $this->rpName,
            $this->interactions,
        );

        $url = $builder->buildWeb2AppUrl();

        $this->assertStringStartsWith('https://sid.demo.sk.ee/v3/device?deviceLinkType=Web2App&', $url);
        $this->assertStringContainsString('sessionToken=token-456', $url);
        $this->assertStringNotContainsString('elapsedSeconds=', $url);
    }

    #[Test]
    public function buildApp2AppUrlReturnsValidUrl(): void
    {
        $builder = new DeviceLinkBuilder(
            $this->response,
            $this->rpChallenge,
            $this->rpName,
            $this->interactions,
        );

        $url = $builder->buildApp2AppUrl();

        $this->assertStringStartsWith('https://sid.demo.sk.ee/v3/device?deviceLinkType=App2App&', $url);
        $this->assertStringContainsString('sessionToken=token-456', $url);
        $this->assertStringNotContainsString('elapsedSeconds=', $url);
    }

    #[Test]
    public function buildUrlWithDeviceLinkType(): void
    {
        $builder = new DeviceLinkBuilder(
            $this->response,
            $this->rpChallenge,
            $this->rpName,
            $this->interactions,
        );

        $qrUrl = $builder->buildUrl(DeviceLinkType::QR);
        $web2appUrl = $builder->buildUrl(DeviceLinkType::WEB2APP);
        $app2appUrl = $builder->buildUrl(DeviceLinkType::APP2APP);

        $this->assertStringContainsString('deviceLinkType=QR', $qrUrl);
        $this->assertStringContainsString('deviceLinkType=Web2App', $web2appUrl);
        $this->assertStringContainsString('deviceLinkType=App2App', $app2appUrl);
    }

    #[Test]
    public function withElapsedSecondsChangesAuthCode(): void
    {
        $builder = new DeviceLinkBuilder(
            $this->response,
            $this->rpChallenge,
            $this->rpName,
            $this->interactions,
        );

        $url0 = $builder->withElapsedSeconds(0)->buildQrCodeUrl();
        $url5 = $builder->withElapsedSeconds(5)->buildQrCodeUrl();

        $this->assertNotSame($url0, $url5);
    }

    #[Test]
    public function withElapsedSecondsIsImmutable(): void
    {
        $builder = new DeviceLinkBuilder(
            $this->response,
            $this->rpChallenge,
            $this->rpName,
            $this->interactions,
        );

        $builder2 = $builder->withElapsedSeconds(10);

        $this->assertNotSame($builder, $builder2);
    }

    #[Test]
    public function withCallbackUrlChangesAuthCode(): void
    {
        $builder = new DeviceLinkBuilder(
            $this->response,
            $this->rpChallenge,
            $this->rpName,
            $this->interactions,
        );

        $urlWithoutCallback = $builder->buildQrCodeUrl();
        $urlWithCallback = $builder->withCallbackUrl('https://example.com/callback')->buildQrCodeUrl();

        $this->assertNotSame($urlWithoutCallback, $urlWithCallback);
    }

    #[Test]
    public function withCallbackUrlIsImmutable(): void
    {
        $builder = new DeviceLinkBuilder(
            $this->response,
            $this->rpChallenge,
            $this->rpName,
            $this->interactions,
        );

        $builder2 = $builder->withCallbackUrl('https://example.com');

        $this->assertNotSame($builder, $builder2);
    }

    #[Test]
    public function withBrokeredRpNameChangesAuthCode(): void
    {
        $builder = new DeviceLinkBuilder(
            $this->response,
            $this->rpChallenge,
            $this->rpName,
            $this->interactions,
        );

        $urlWithoutBrokered = $builder->buildQrCodeUrl();
        $urlWithBrokered = $builder->withBrokeredRpName('Brokered RP')->buildQrCodeUrl();

        $this->assertNotSame($urlWithoutBrokered, $urlWithBrokered);
    }

    #[Test]
    public function withBrokeredRpNameIsImmutable(): void
    {
        $builder = new DeviceLinkBuilder(
            $this->response,
            $this->rpChallenge,
            $this->rpName,
            $this->interactions,
        );

        $builder2 = $builder->withBrokeredRpName('Brokered');

        $this->assertNotSame($builder, $builder2);
    }

    #[Test]
    public function withSchemeNameChangesAuthCode(): void
    {
        $builder = new DeviceLinkBuilder(
            $this->response,
            $this->rpChallenge,
            $this->rpName,
            $this->interactions,
        );

        $urlProduction = $builder->buildQrCodeUrl();
        $urlDemo = $builder->withDemoEnvironment()->buildQrCodeUrl();

        $this->assertNotSame($urlProduction, $urlDemo);
    }

    #[Test]
    public function chainingMultipleWithMethods(): void
    {
        $builder = new DeviceLinkBuilder(
            $this->response,
            $this->rpChallenge,
            $this->rpName,
            $this->interactions,
        );

        $url = $builder
            ->withElapsedSeconds(5)
            ->withCallbackUrl('https://example.com')
            ->withBrokeredRpName('Brokered')
            ->buildQrCodeUrl();

        $this->assertStringStartsWith('https://sid.demo.sk.ee/v3/device?deviceLinkType=QR&', $url);
    }
}
