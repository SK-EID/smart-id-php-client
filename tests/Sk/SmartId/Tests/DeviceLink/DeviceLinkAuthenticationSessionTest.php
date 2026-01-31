<?php

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
        );

        $url = $session->buildWeb2AppUrl();

        $this->assertStringStartsWith('https://sid.demo.sk.ee/v3/device?deviceLinkType=Web2App&', $url);
    }

    #[Test]
    public function buildWeb2AppUrlWithExplicitElapsedSeconds(): void
    {
        $session = new DeviceLinkAuthenticationSession(
            $this->response,
            $this->rpChallenge,
            $this->rpName,
            $this->interactions,
            $this->verificationCode,
        );

        $url = $session->buildWeb2AppUrl(10);

        $this->assertStringStartsWith('https://sid.demo.sk.ee/v3/device?deviceLinkType=Web2App&', $url);
    }

    #[Test]
    public function buildApp2AppUrlReturnsValidUrl(): void
    {
        $session = new DeviceLinkAuthenticationSession(
            $this->response,
            $this->rpChallenge,
            $this->rpName,
            $this->interactions,
            $this->verificationCode,
        );

        $url = $session->buildApp2AppUrl();

        $this->assertStringStartsWith('https://sid.demo.sk.ee/v3/device?deviceLinkType=App2App&', $url);
    }

    #[Test]
    public function buildApp2AppUrlWithExplicitElapsedSeconds(): void
    {
        $session = new DeviceLinkAuthenticationSession(
            $this->response,
            $this->rpChallenge,
            $this->rpName,
            $this->interactions,
            $this->verificationCode,
        );

        $url = $session->buildApp2AppUrl(15);

        $this->assertStringStartsWith('https://sid.demo.sk.ee/v3/device?deviceLinkType=App2App&', $url);
    }
}
