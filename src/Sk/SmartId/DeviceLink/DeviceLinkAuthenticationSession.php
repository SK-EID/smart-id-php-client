<?php

declare(strict_types=1);

namespace Sk\SmartId\DeviceLink;

use Sk\SmartId\Model\Interaction;

class DeviceLinkAuthenticationSession
{
    private readonly float $createdAt;

    /**
     * @param Interaction[] $interactions
     */
    public function __construct(
        private readonly DeviceLinkAuthenticationResponse $response,
        private readonly string $rpChallenge,
        private readonly string $rpName,
        private readonly array $interactions,
        private readonly string $verificationCode,
    ) {
        $this->createdAt = microtime(true);
    }

    public function getSessionId(): string
    {
        return $this->response->getSessionID();
    }

    public function getVerificationCode(): string
    {
        return $this->verificationCode;
    }

    public function getElapsedSeconds(): int
    {
        return (int) floor(microtime(true) - $this->createdAt);
    }

    public function createDeviceLinkBuilder(): DeviceLinkBuilder
    {
        return new DeviceLinkBuilder(
            $this->response,
            $this->rpChallenge,
            $this->rpName,
            $this->interactions,
        );
    }

    public function buildQrCodeUrl(?int $elapsedSeconds = null): string
    {
        return $this->buildUrlWithElapsed($elapsedSeconds)->buildQrCodeUrl();
    }

    public function buildWeb2AppUrl(?int $elapsedSeconds = null): string
    {
        return $this->buildUrlWithElapsed($elapsedSeconds)->buildWeb2AppUrl();
    }

    public function buildApp2AppUrl(?int $elapsedSeconds = null): string
    {
        return $this->buildUrlWithElapsed($elapsedSeconds)->buildApp2AppUrl();
    }

    private function buildUrlWithElapsed(?int $elapsedSeconds): DeviceLinkBuilder
    {
        $builder = $this->createDeviceLinkBuilder();

        return $builder->withElapsedSeconds($elapsedSeconds ?? $this->getElapsedSeconds());
    }

    public function getRpChallenge(): string
    {
        return $this->rpChallenge;
    }

    public function getResponse(): DeviceLinkAuthenticationResponse
    {
        return $this->response;
    }
}
