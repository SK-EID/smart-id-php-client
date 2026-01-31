<?php

declare(strict_types=1);

namespace Sk\SmartId\DeviceLink;

use Sk\SmartId\Enum\DeviceLinkType;
use Sk\SmartId\Model\Interaction;
use Sk\SmartId\Util\AuthCodeCalculator;

class DeviceLinkBuilder
{
    private DeviceLinkAuthenticationResponse $response;

    private string $rpChallenge;

    private string $rpName;

    /** @var Interaction[] */
    private array $interactions;

    private int $elapsedSeconds = 0;

    private ?string $callbackUrl = null;

    private ?string $brokeredRpName = null;

    private bool $unprotectedLink = false;

    /**
     * @param Interaction[] $interactions
     */
    public function __construct(
        DeviceLinkAuthenticationResponse $response,
        string $rpChallenge,
        string $rpName,
        array $interactions,
    ) {
        $this->response = $response;
        $this->rpChallenge = $rpChallenge;
        $this->rpName = $rpName;
        $this->interactions = $interactions;
    }

    public function withElapsedSeconds(int $elapsedSeconds): self
    {
        $clone = clone $this;
        $clone->elapsedSeconds = $elapsedSeconds;

        return $clone;
    }

    public function withCallbackUrl(string $callbackUrl): self
    {
        $clone = clone $this;
        $clone->callbackUrl = $callbackUrl;

        return $clone;
    }

    public function withBrokeredRpName(string $brokeredRpName): self
    {
        $clone = clone $this;
        $clone->brokeredRpName = $brokeredRpName;

        return $clone;
    }

    public function withUnprotectedLink(bool $unprotectedLink): self
    {
        $clone = clone $this;
        $clone->unprotectedLink = $unprotectedLink;

        return $clone;
    }

    public function buildQrCodeUrl(): string
    {
        return $this->buildUrl(DeviceLinkType::QR);
    }

    public function buildWeb2AppUrl(): string
    {
        return $this->buildUrl(DeviceLinkType::WEB2APP);
    }

    public function buildApp2AppUrl(): string
    {
        return $this->buildUrl(DeviceLinkType::APP2APP);
    }

    public function buildUrl(DeviceLinkType $type): string
    {
        $authCode = AuthCodeCalculator::calculate(
            $this->response->getSessionSecret(),
            $this->rpChallenge,
            $this->rpName,
            $this->interactions,
            $this->elapsedSeconds,
            $this->callbackUrl,
            $this->brokeredRpName,
            $this->unprotectedLink,
        );

        $params = [
            'sessionToken' => $this->response->getSessionToken(),
            'authCode' => $authCode,
        ];

        return $this->response->getDeviceLinkBase() . '/' . $type->value . '?' . http_build_query($params);
    }
}
