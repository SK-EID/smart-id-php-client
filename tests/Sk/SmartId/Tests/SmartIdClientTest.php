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

namespace Sk\SmartId\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\Api\SmartIdConnector;
use Sk\SmartId\DeviceLink\DeviceLinkAuthenticationRequestBuilder;
use Sk\SmartId\Notification\NotificationAuthenticationRequestBuilder;
use Sk\SmartId\Session\SessionStatusPoller;
use Sk\SmartId\SmartIdClient;
use Sk\SmartId\Ssl\SslPinnedPublicKeyStore;

class SmartIdClientTest extends TestCase
{
    private SmartIdClient $client;

    protected function setUp(): void
    {
        $this->client = new SmartIdClient(
            'test-uuid',
            'Test RP',
            'https://sid.demo.sk.ee/smart-id-rp/v3',
            SslPinnedPublicKeyStore::loadDemo(),
        );
    }

    #[Test]
    public function gettersReturnConstructorValues(): void
    {
        $this->assertSame('test-uuid', $this->client->getRelyingPartyUUID());
        $this->assertSame('Test RP', $this->client->getRelyingPartyName());
        $this->assertSame('https://sid.demo.sk.ee/smart-id-rp/v3', $this->client->getHostUrl());
    }

    #[Test]
    public function createNotificationAuthenticationReturnsBuilder(): void
    {
        $connector = $this->createMock(SmartIdConnector::class);
        $this->client->setConnector($connector);

        $builder = $this->client->createNotificationAuthentication();

        $this->assertInstanceOf(NotificationAuthenticationRequestBuilder::class, $builder);
    }

    #[Test]
    public function createDeviceLinkAuthenticationReturnsBuilder(): void
    {
        $connector = $this->createMock(SmartIdConnector::class);
        $this->client->setConnector($connector);

        $builder = $this->client->createDeviceLinkAuthentication();

        $this->assertInstanceOf(DeviceLinkAuthenticationRequestBuilder::class, $builder);
    }

    #[Test]
    public function getSessionStatusPollerReturnsPoller(): void
    {
        $connector = $this->createMock(SmartIdConnector::class);
        $this->client->setConnector($connector);

        $poller = $this->client->getSessionStatusPoller();

        $this->assertInstanceOf(SessionStatusPoller::class, $poller);
    }

    #[Test]
    public function getSessionStatusPollerReturnsSameInstance(): void
    {
        $connector = $this->createMock(SmartIdConnector::class);
        $this->client->setConnector($connector);

        $poller1 = $this->client->getSessionStatusPoller();
        $poller2 = $this->client->getSessionStatusPoller();

        $this->assertSame($poller1, $poller2);
    }

    #[Test]
    public function setPollTimeoutMsResetsPoller(): void
    {
        $connector = $this->createMock(SmartIdConnector::class);
        $this->client->setConnector($connector);

        $poller1 = $this->client->getSessionStatusPoller();

        $result = $this->client->setPollTimeoutMs(60000);

        $this->assertSame($this->client, $result);

        $poller2 = $this->client->getSessionStatusPoller();
        $this->assertNotSame($poller1, $poller2);
    }

    #[Test]
    public function setPollIntervalMsResetsPoller(): void
    {
        $connector = $this->createMock(SmartIdConnector::class);
        $this->client->setConnector($connector);

        $poller1 = $this->client->getSessionStatusPoller();

        $result = $this->client->setPollIntervalMs(2000);

        $this->assertSame($this->client, $result);

        $poller2 = $this->client->getSessionStatusPoller();
        $this->assertNotSame($poller1, $poller2);
    }

    #[Test]
    public function setConnectorReturnsSelf(): void
    {
        $connector = $this->createMock(SmartIdConnector::class);

        $result = $this->client->setConnector($connector);

        $this->assertSame($this->client, $result);
    }

    #[Test]
    public function getConnectorReturnsSameInstance(): void
    {
        $connector = $this->createMock(SmartIdConnector::class);
        $this->client->setConnector($connector);

        $this->assertSame($connector, $this->client->getConnector());
    }

    #[Test]
    public function getConnectorCreatesSmartIdRestConnectorLazily(): void
    {
        // Don't set a connector — let it create one lazily
        $connector = $this->client->getConnector();

        $this->assertInstanceOf(\Sk\SmartId\Api\SmartIdRestConnector::class, $connector);
    }

    #[Test]
    public function getConnectorReturnsSameLazyInstance(): void
    {
        $connector1 = $this->client->getConnector();
        $connector2 = $this->client->getConnector();

        $this->assertSame($connector1, $connector2);
    }
}
