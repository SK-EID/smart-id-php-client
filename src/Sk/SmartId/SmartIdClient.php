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

namespace Sk\SmartId;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sk\SmartId\Api\SmartIdConnector;
use Sk\SmartId\Api\SmartIdRestConnector;
use Sk\SmartId\DeviceLink\DeviceLinkAuthenticationRequestBuilder;
use Sk\SmartId\Notification\NotificationAuthenticationRequestBuilder;
use Sk\SmartId\Session\SessionStatusPoller;
use Sk\SmartId\Ssl\SslPinnedPublicKeyStore;
use Sk\SmartId\Validation\AuthenticationResponseValidator;
use Sk\SmartId\Validation\OcspCertificateRevocationChecker;

class SmartIdClient
{
    private ?SmartIdConnector $connector = null;

    private ?SessionStatusPoller $sessionStatusPoller = null;

    private int $pollTimeoutMs = 30000;

    private int $pollIntervalMs = 1000;

    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly string $relyingPartyUUID,
        private readonly string $relyingPartyName,
        private readonly string $hostUrl,
        private readonly SslPinnedPublicKeyStore $sslPinnedKeys,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function createNotificationAuthentication(): NotificationAuthenticationRequestBuilder
    {
        return new NotificationAuthenticationRequestBuilder(
            $this->getConnector(),
            $this->relyingPartyUUID,
            $this->relyingPartyName,
            $this->logger,
        );
    }

    public function createDeviceLinkAuthentication(): DeviceLinkAuthenticationRequestBuilder
    {
        return new DeviceLinkAuthenticationRequestBuilder(
            $this->getConnector(),
            $this->relyingPartyUUID,
            $this->relyingPartyName,
            $this->logger,
        );
    }

    public function getSessionStatusPoller(): SessionStatusPoller
    {
        if ($this->sessionStatusPoller === null) {
            $this->sessionStatusPoller = new SessionStatusPoller(
                $this->getConnector(),
                $this->pollTimeoutMs,
                $this->pollIntervalMs,
                $this->logger,
            );
        }

        return $this->sessionStatusPoller;
    }

    public function setPollTimeoutMs(int $pollTimeoutMs): self
    {
        $this->pollTimeoutMs = $pollTimeoutMs;
        $this->sessionStatusPoller = null;

        return $this;
    }

    public function setPollIntervalMs(int $pollIntervalMs): self
    {
        $this->pollIntervalMs = $pollIntervalMs;
        $this->sessionStatusPoller = null;

        return $this;
    }

    public function getConnector(): SmartIdConnector
    {
        if ($this->connector === null) {
            $this->connector = new SmartIdRestConnector(
                $this->hostUrl,
                $this->sslPinnedKeys,
                $this->logger,
            );
        }

        return $this->connector;
    }

    /**
     * @internal For testing or custom connector injection.
     */
    public function setConnector(SmartIdConnector $connector): self
    {
        $this->connector = $connector;

        return $this;
    }

    public function createAuthenticationResponseValidator(): AuthenticationResponseValidator
    {
        return new AuthenticationResponseValidator($this->logger);
    }

    public function createOcspChecker(): OcspCertificateRevocationChecker
    {
        return OcspCertificateRevocationChecker::create($this->logger);
    }

    public function getRelyingPartyUUID(): string
    {
        return $this->relyingPartyUUID;
    }

    public function getRelyingPartyName(): string
    {
        return $this->relyingPartyName;
    }

    public function getHostUrl(): string
    {
        return $this->hostUrl;
    }
}
