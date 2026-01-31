<?php

declare(strict_types=1);

namespace Sk\SmartId\DeviceLink;

use Sk\SmartId\Enum\DeviceLinkType;
use Sk\SmartId\Enum\SessionType;
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

    private SessionType $sessionType = SessionType::AUTHENTICATION;

    private string $version = '1.0';

    private string $lang = 'eng';

    private string $schemeName = AuthCodeCalculator::SCHEME_NAME_PRODUCTION;

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

    public function withSessionType(SessionType $sessionType): self
    {
        $clone = clone $this;
        $clone->sessionType = $sessionType;

        return $clone;
    }

    public function withLang(string $lang): self
    {
        $clone = clone $this;
        $clone->lang = $lang;

        return $clone;
    }

    public function withSchemeName(string $schemeName): self
    {
        $clone = clone $this;
        $clone->schemeName = $schemeName;

        return $clone;
    }

    public function withDemoEnvironment(): self
    {
        return $this->withSchemeName(AuthCodeCalculator::SCHEME_NAME_DEMO);
    }

    public function withProductionEnvironment(): self
    {
        return $this->withSchemeName(AuthCodeCalculator::SCHEME_NAME_PRODUCTION);
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
        $unprotectedDeviceLink = $this->buildUnprotectedDeviceLink($type);

        $authCode = AuthCodeCalculator::calculate(
            $this->response->getSessionSecret(),
            $this->rpChallenge,
            $this->rpName,
            $this->interactions,
            $this->callbackUrl,
            $this->brokeredRpName,
            $unprotectedDeviceLink,
            $this->schemeName,
        );

        return $unprotectedDeviceLink . '&authCode=' . $authCode;
    }

    private function buildUnprotectedDeviceLink(DeviceLinkType $type): string
    {
        $params = 'deviceLinkType=' . $type->value;

        if ($type === DeviceLinkType::QR) {
            $params .= '&elapsedSeconds=' . $this->elapsedSeconds;
        }

        $params .= '&sessionToken=' . $this->response->getSessionToken();
        $params .= '&sessionType=' . $this->sessionType->value;
        $params .= '&version=' . $this->version;
        $params .= '&lang=' . $this->lang;

        if ($type !== DeviceLinkType::QR && $this->callbackUrl !== null) {
            $params .= '&initialCallbackUrl=' . $this->callbackUrl;
        }

        return $this->response->getDeviceLinkBase() . '?' . $params;
    }
}
