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

namespace Sk\SmartId\DeviceLink;

use Sk\SmartId\Enum\DeviceLinkType;
use Sk\SmartId\Enum\SchemeName;
use Sk\SmartId\Enum\SessionType;
use Sk\SmartId\Util\AuthCodeCalculator;

/**
 * Builder for constructing secure device link URLs with authentication codes.
 *
 * This class generates URLs that can be used to initiate Smart-ID authentication
 * from the user's device. It supports two types of device links:
 * - QR Code: User scans with their phone camera, requires elapsed seconds tracking
 * - Web2App: Deep link for mobile web browsers to open the Smart-ID app
 *
 * Each generated URL includes an authentication code (authCode) calculated from
 * the session secret and request parameters. This ensures URL integrity and
 * prevents tampering. The Smart-ID app validates this code before processing.
 *
 * The builder uses an immutable pattern - each configuration method returns
 * a new instance, allowing safe reuse and modification of configurations.
 *
 * @see DeviceLinkAuthenticationSession::createDeviceLinkBuilder() for typical instantiation
 * @see AuthCodeCalculator for the authentication code calculation algorithm
 */
class DeviceLinkBuilder
{
    private DeviceLinkAuthenticationResponse $response;

    private string $rpChallenge;

    private string $rpName;

    private string $interactionsBase64;

    private int $elapsedSeconds = 0;

    private ?string $callbackUrl = null;

    private ?string $brokeredRpName = null;

    private SessionType $sessionType = SessionType::AUTHENTICATION;

    private string $version = '1.0';

    private string $lang = 'eng';

    private SchemeName $schemeName = SchemeName::PRODUCTION;

    /**
     * @param string $interactionsBase64 Base64-encoded JSON string of interactions
     */
    public function __construct(
        DeviceLinkAuthenticationResponse $response,
        string $rpChallenge,
        string $rpName,
        string $interactionsBase64,
    ) {
        $this->response = $response;
        $this->rpChallenge = $rpChallenge;
        $this->rpName = $rpName;
        $this->interactionsBase64 = $interactionsBase64;
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

    public function withLang(string $lang): self
    {
        $clone = clone $this;
        $clone->lang = $lang;

        return $clone;
    }

    public function withSchemeName(SchemeName $schemeName): self
    {
        $clone = clone $this;
        $clone->schemeName = $schemeName;

        return $clone;
    }

    public function withDemoEnvironment(): self
    {
        return $this->withSchemeName(SchemeName::DEMO);
    }

    public function withProductionEnvironment(): self
    {
        return $this->withSchemeName(SchemeName::PRODUCTION);
    }

    public function buildQrCodeUrl(): string
    {
        return $this->buildUrl(DeviceLinkType::QR);
    }

    public function buildWeb2AppUrl(): string
    {
        return $this->buildUrl(DeviceLinkType::WEB2APP);
    }

    public function buildUrl(DeviceLinkType $type): string
    {
        if ($type === DeviceLinkType::WEB2APP && $this->callbackUrl === null) {
            throw new \InvalidArgumentException(
                "Parameter 'callbackUrl' must be provided when deviceLinkType is WEB2APP. " .
                'Use withCallbackUrl() to set it. Example: https://your-app.com/callback',
            );
        }

        // QR code flow must NOT have initialCallbackUrl
        if ($type === DeviceLinkType::QR && $this->callbackUrl !== null) {
            throw new \InvalidArgumentException(
                "Parameter 'callbackUrl' must be empty when deviceLinkType is QR",
            );
        }

        $unprotectedDeviceLink = $this->buildUnprotectedDeviceLink($type);

        $authCode = AuthCodeCalculator::calculate(
            $this->response->getSessionSecret(),
            $this->rpChallenge,
            $this->rpName,
            $this->interactionsBase64,
            $unprotectedDeviceLink,
            $this->callbackUrl,
            $this->brokeredRpName,
            $this->schemeName,
        );

        return $unprotectedDeviceLink . '&authCode=' . $authCode;
    }

    private function buildUnprotectedDeviceLink(DeviceLinkType $type): string
    {
        // URL parameter order must match exactly what Smart-ID backend expects
        // See: https://sk-eid.github.io/smart-id-documentation/rp-api/authcode.html
        $params = 'deviceLinkType=' . $type->value;

        if ($type === DeviceLinkType::QR) {
            $params .= '&elapsedSeconds=' . $this->elapsedSeconds;
        }

        $params .= '&sessionToken=' . $this->response->getSessionToken();
        $params .= '&sessionType=' . $this->sessionType->value;
        $params .= '&version=' . $this->version;
        $params .= '&lang=' . $this->lang;

        // Note: initialCallbackUrl is NOT added to the URL - it's only used in authCode calculation
        // The Smart-ID app retrieves the callback URL from the backend using the session token

        return $this->response->getDeviceLinkBase() . '?' . $params;
    }
}
