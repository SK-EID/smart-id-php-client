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

namespace Sk\SmartId\Tests\Api;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Sk\SmartId\Api\SmartIdRestConnector;
use Sk\SmartId\DeviceLink\DeviceLinkAuthenticationRequest;
use Sk\SmartId\Enum\HashAlgorithm;
use Sk\SmartId\Exception\InvalidParametersException;
use Sk\SmartId\Exception\SessionNotFoundException;
use Sk\SmartId\Exception\SmartIdException;
use Sk\SmartId\Exception\UnauthorizedException;
use Sk\SmartId\Exception\UserAccountException;
use Sk\SmartId\DeviceLink\DeviceLinkInteraction;
use Sk\SmartId\Notification\NotificationAuthenticationRequest;
use Sk\SmartId\Notification\NotificationInteraction;

class SmartIdRestConnectorTest extends TestCase
{
    private ClientInterface $httpClient;

    private RequestFactoryInterface $requestFactory;

    private StreamFactoryInterface $streamFactory;

    private SmartIdRestConnector $connector;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->requestFactory = $this->createMock(RequestFactoryInterface::class);
        $this->streamFactory = $this->createMock(StreamFactoryInterface::class);

        $this->connector = SmartIdRestConnector::createForTesting(
            'https://sid.demo.sk.ee/smart-id-rp/v3',
            $this->httpClient,
            $this->requestFactory,
            $this->streamFactory,
        );
    }

    #[Test]
    public function initiateDeviceLinkAuthenticationReturnsResponse(): void
    {
        $responseBody = json_encode([
            'sessionID' => 'session-123',
            'sessionToken' => 'token-456',
            'sessionSecret' => 'secret-789',
            'deviceLinkBase' => 'https://sid.demo.sk.ee/v3/device',
        ]);

        $this->setupMockRequest('POST', $responseBody, 200);

        $request = new DeviceLinkAuthenticationRequest(
            'rp-uuid',
            'Test RP',
            base64_encode('challenge'),
            HashAlgorithm::SHA512,
            [DeviceLinkInteraction::displayTextAndPin('Test')],
        );

        $response = $this->connector->initiateDeviceLinkAuthentication($request);

        $this->assertSame('session-123', $response->getSessionID());
        $this->assertSame('token-456', $response->getSessionToken());
        $this->assertSame('secret-789', $response->getSessionSecret());
    }

    #[Test]
    public function initiateNotificationAuthenticationByDocumentNumberReturnsResponse(): void
    {
        $responseBody = json_encode(['sessionID' => 'session-123']);

        $this->setupMockRequest('POST', $responseBody, 200);

        $request = new NotificationAuthenticationRequest(
            'rp-uuid',
            'Test RP',
            base64_encode('challenge'),
            HashAlgorithm::SHA512,
            [NotificationInteraction::displayTextAndPin('Test')],
        );

        $response = $this->connector->initiateNotificationAuthentication(
            $request,
            'PNOEE-12345678901',
        );

        $this->assertSame('session-123', $response->getSessionID());
    }

    #[Test]
    public function initiateNotificationAuthenticationBySemanticsIdentifierReturnsResponse(): void
    {
        $responseBody = json_encode(['sessionID' => 'session-456']);

        $this->setupMockRequest('POST', $responseBody, 200);

        $request = new NotificationAuthenticationRequest(
            'rp-uuid',
            'Test RP',
            base64_encode('challenge'),
            HashAlgorithm::SHA512,
            [NotificationInteraction::displayTextAndPin('Test')],
        );

        $response = $this->connector->initiateNotificationAuthentication(
            $request,
            null,
            'PNOEE-12345678901',
        );

        $this->assertSame('session-456', $response->getSessionID());
    }

    #[Test]
    public function initiateNotificationAuthenticationThrowsWhenBothParametersNull(): void
    {
        $request = new NotificationAuthenticationRequest(
            'rp-uuid',
            'Test RP',
            base64_encode('challenge'),
            HashAlgorithm::SHA512,
            [],
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Either documentNumber or semanticsIdentifier must be provided');

        $this->connector->initiateNotificationAuthentication($request);
    }

    #[Test]
    public function getSessionStatusReturnsRunningStatus(): void
    {
        $responseBody = json_encode(['state' => 'RUNNING']);

        $this->setupMockRequest('GET', $responseBody, 200);

        $status = $this->connector->getSessionStatus('session-123');

        $this->assertTrue($status->isRunning());
    }

    #[Test]
    public function getSessionStatusReturnsCompleteStatusWithResult(): void
    {
        $responseBody = json_encode([
            'state' => 'COMPLETE',
            'result' => [
                'endResult' => 'OK',
                'documentNumber' => 'PNOEE-12345678901',
            ],
        ]);

        $this->setupMockRequest('GET', $responseBody, 200);

        $status = $this->connector->getSessionStatus('session-123');

        $this->assertTrue($status->isComplete());
        $this->assertTrue($status->getResult()->isOk());
    }

    #[Test]
    public function getSessionStatusWithTimeoutIncludesTimeoutInUrl(): void
    {
        $responseBody = json_encode(['state' => 'RUNNING']);

        $request = $this->createMock(RequestInterface::class);
        $request->method('withHeader')->willReturnSelf();

        $this->requestFactory->expects($this->once())
            ->method('createRequest')
            ->with('GET', $this->stringContains('timeoutMs=30000'))
            ->willReturn($request);

        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn($responseBody);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($stream);

        $this->httpClient->method('sendRequest')->willReturn($response);

        $this->connector->getSessionStatus('session-123', 30000);
    }

    #[Test]
    public function getSessionStatusThrowsSessionNotFoundFor404(): void
    {
        $this->setupMockRequest('GET', '', 404);

        $this->expectException(SessionNotFoundException::class);
        $this->expectExceptionMessage('Session not found');

        $this->connector->getSessionStatus('nonexistent-session');
    }

    #[Test]
    public function initiateDeviceLinkAuthenticationThrowsSmartIdExceptionForHttpError(): void
    {
        $this->setupMockRequest('POST', '', 500);

        $request = new DeviceLinkAuthenticationRequest(
            'rp-uuid',
            'Test RP',
            base64_encode('challenge'),
            HashAlgorithm::SHA512,
            [],
        );

        $this->expectException(SmartIdException::class);
        $this->expectExceptionMessage('Smart-ID service temporarily unavailable');

        $this->connector->initiateDeviceLinkAuthentication($request);
    }

    #[Test]
    public function throwsInvalidParametersExceptionFor400(): void
    {
        $this->setupMockRequest('POST', '{"message":"Invalid hash"}', 400);

        $request = new DeviceLinkAuthenticationRequest(
            'rp-uuid',
            'Test RP',
            base64_encode('challenge'),
            HashAlgorithm::SHA512,
            [],
        );

        $this->expectException(InvalidParametersException::class);
        $this->expectExceptionMessage('Invalid request parameters: Invalid hash');

        $this->connector->initiateDeviceLinkAuthentication($request);
    }

    #[Test]
    public function throwsUnauthorizedExceptionFor401(): void
    {
        $this->setupMockRequest('POST', '', 401);

        $request = new DeviceLinkAuthenticationRequest(
            'rp-uuid',
            'Test RP',
            base64_encode('challenge'),
            HashAlgorithm::SHA512,
            [],
        );

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('invalid Relying Party credentials');

        $this->connector->initiateDeviceLinkAuthentication($request);
    }

    #[Test]
    public function throwsUnauthorizedExceptionFor403(): void
    {
        $this->setupMockRequest('POST', '', 403);

        $request = new DeviceLinkAuthenticationRequest(
            'rp-uuid',
            'Test RP',
            base64_encode('challenge'),
            HashAlgorithm::SHA512,
            [],
        );

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('Forbidden');

        $this->connector->initiateDeviceLinkAuthentication($request);
    }

    #[Test]
    public function throwsUserAccountExceptionFor471(): void
    {
        $this->setupMockRequest('POST', '', 471);

        $request = new DeviceLinkAuthenticationRequest(
            'rp-uuid',
            'Test RP',
            base64_encode('challenge'),
            HashAlgorithm::SHA512,
            [],
        );

        $this->expectException(UserAccountException::class);
        $this->expectExceptionMessage('No suitable account');

        $this->connector->initiateDeviceLinkAuthentication($request);
    }

    #[Test]
    public function throwsUserAccountExceptionFor472(): void
    {
        $this->setupMockRequest('POST', '', 472);

        $request = new DeviceLinkAuthenticationRequest(
            'rp-uuid',
            'Test RP',
            base64_encode('challenge'),
            HashAlgorithm::SHA512,
            [],
        );

        $this->expectException(UserAccountException::class);
        $this->expectExceptionMessage('Person should view Smart-ID app');

        $this->connector->initiateDeviceLinkAuthentication($request);
    }

    #[Test]
    public function throwsUserAccountExceptionFor480(): void
    {
        $this->setupMockRequest('POST', '', 480);

        $request = new DeviceLinkAuthenticationRequest(
            'rp-uuid',
            'Test RP',
            base64_encode('challenge'),
            HashAlgorithm::SHA512,
            [],
        );

        $this->expectException(UserAccountException::class);
        $this->expectExceptionMessage('Client-side API is too old');

        $this->connector->initiateDeviceLinkAuthentication($request);
    }

    #[Test]
    public function initiateDeviceLinkAuthenticationThrowsSessionNotFoundFor404(): void
    {
        $this->setupMockRequest('POST', '', 404);

        $request = new DeviceLinkAuthenticationRequest(
            'rp-uuid',
            'Test RP',
            base64_encode('challenge'),
            HashAlgorithm::SHA512,
            [],
        );

        $this->expectException(SessionNotFoundException::class);

        $this->connector->initiateDeviceLinkAuthentication($request);
    }

    private function setupMockRequest(string $method, string $responseBody, int $statusCode): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('withHeader')->willReturnSelf();
        $request->method('withBody')->willReturnSelf();

        $this->requestFactory->method('createRequest')
            ->with($method, $this->anything())
            ->willReturn($request);

        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn($responseBody);

        $this->streamFactory->method('createStream')->willReturn($stream);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($statusCode);
        $response->method('getBody')->willReturn($stream);

        $this->httpClient->method('sendRequest')->willReturn($response);
    }
}
