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
use Sk\SmartId\DeviceLink\DeviceLinkAuthenticationSession;
use Sk\SmartId\DeviceLink\DeviceLinkBuilder;
use Sk\SmartId\Model\Interaction;

class DeviceLinkAuthenticationSessionTest extends TestCase
{
    private DeviceLinkAuthenticationResponse $response;

    private string $rpChallenge;

    private string $rpName;

    /** @var Interaction[] */
    private array $interactions;

    private string $verificationCode;

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
        $this->verificationCode = '1234';
    }

    #[Test]
    public function getSessionIdReturnsCorrectValue(): void
    {
        $session = new DeviceLinkAuthenticationSession(
            $this->response,
            $this->rpChallenge,
            $this->rpName,
            $this->interactions,
            $this->verificationCode,
        );

        $this->assertSame('session-123', $session->getSessionId());
    }

    #[Test]
    public function getVerificationCodeReturnsCorrectValue(): void
    {
        $session = new DeviceLinkAuthenticationSession(
            $this->response,
            $this->rpChallenge,
            $this->rpName,
            $this->interactions,
            $this->verificationCode,
        );

        $this->assertSame('1234', $session->getVerificationCode());
    }

    #[Test]
    public function getRpChallengeReturnsCorrectValue(): void
    {
        $session = new DeviceLinkAuthenticationSession(
            $this->response,
            $this->rpChallenge,
            $this->rpName,
            $this->interactions,
            $this->verificationCode,
        );

        $this->assertSame($this->rpChallenge, $session->getRpChallenge());
    }

    #[Test]
    public function getResponseReturnsCorrectValue(): void
    {
        $session = new DeviceLinkAuthenticationSession(
            $this->response,
            $this->rpChallenge,
            $this->rpName,
            $this->interactions,
            $this->verificationCode,
        );

        $this->assertSame($this->response, $session->getResponse());
    }

    #[Test]
    public function getElapsedSecondsReturnsNonNegativeValue(): void
    {
        $session = new DeviceLinkAuthenticationSession(
            $this->response,
            $this->rpChallenge,
            $this->rpName,
            $this->interactions,
            $this->verificationCode,
        );

        $this->assertGreaterThanOrEqual(0, $session->getElapsedSeconds());
    }

    #[Test]
    public function createDeviceLinkBuilderReturnsBuilder(): void
    {
        $session = new DeviceLinkAuthenticationSession(
            $this->response,
            $this->rpChallenge,
            $this->rpName,
            $this->interactions,
            $this->verificationCode,
        );

        $builder = $session->createDeviceLinkBuilder();

        $this->assertInstanceOf(DeviceLinkBuilder::class, $builder);
    }

    #[Test]
    public function buildQrCodeUrlReturnsValidUrl(): void
    {
        $session = new DeviceLinkAuthenticationSession(
            $this->response,
            $this->rpChallenge,
            $this->rpName,
            $this->interactions,
            $this->verificationCode,
        );

        $url = $session->buildQrCodeUrl();

        $this->assertStringStartsWith('https://sid.demo.sk.ee/v3/device?deviceLinkType=QR&', $url);
    }

    #[Test]
    public function buildQrCodeUrlWithExplicitElapsedSeconds(): void
    {
        $session = new DeviceLinkAuthenticationSession(
            $this->response,
            $this->rpChallenge,
            $this->rpName,
            $this->interactions,
            $this->verificationCode,
        );

        $url = $session->buildQrCodeUrl(5);

        $this->assertStringStartsWith('https://sid.demo.sk.ee/v3/device?deviceLinkType=QR&', $url);
    }

    #[Test]
    public function buildWeb2AppUrlReturnsValidUrl(): void
    {
        $session = new DeviceLinkAuthenticationSession(
            $this->response,
            $this->rpChallenge,
            $this->rpName,
            $this->interactions,
            $this->verificationCode,
            'https://example.com/callback?value=random123',
        );

        $url = $session->buildWeb2AppUrl();

        $this->assertStringStartsWith('https://sid.demo.sk.ee/v3/device?deviceLinkType=Web2App&', $url);
    }

    #[Test]
    public function buildWeb2AppUrlThrowsWithoutCallbackUrl(): void
    {
        $session = new DeviceLinkAuthenticationSession(
            $this->response,
            $this->rpChallenge,
            $this->rpName,
            $this->interactions,
            $this->verificationCode,
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('callbackUrl');

        $session->buildWeb2AppUrl();
    }

}
