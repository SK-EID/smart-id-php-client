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

namespace Sk\SmartId\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sk\SmartId\DeviceLink\DeviceLinkAuthenticationRequest;
use Sk\SmartId\DeviceLink\DeviceLinkAuthenticationResponse;
use Sk\SmartId\Exception\InvalidParametersException;
use Sk\SmartId\Exception\SessionNotFoundException;
use Sk\SmartId\Exception\SmartIdException;
use Sk\SmartId\Exception\UnauthorizedException;
use Sk\SmartId\Exception\UnderMaintenanceException;
use Sk\SmartId\Exception\UserAccountException;
use Sk\SmartId\Notification\NotificationAuthenticationRequest;
use Sk\SmartId\Notification\NotificationAuthenticationResponse;
use Sk\SmartId\Session\SessionStatus;
use Sk\SmartId\Ssl\SslPinnedPublicKeyStore;

class SmartIdRestConnector implements SmartIdConnector
{
    private readonly ClientInterface $httpClient;

    private readonly RequestFactoryInterface $requestFactory;

    private readonly StreamFactoryInterface $streamFactory;

    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly string $baseUrl,
        SslPinnedPublicKeyStore $sslPinnedKeys,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
        $this->httpClient = new Client([
        'verify' => true,
        'curl' => [
                CURLOPT_PINNEDPUBLICKEY => $sslPinnedKeys->toPinnedKeyString(),
            ],
        ]);
        $this->requestFactory = new HttpFactory();
        $this->streamFactory = new HttpFactory();
    }

    /**
     * @internal For unit testing only. Do not use in production code.
     */
    public static function createForTesting(
        string $baseUrl,
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
    ): self {
        $instance = (new \ReflectionClass(self::class))->newInstanceWithoutConstructor();

        $setClosed = function (string $prop, mixed $value) use ($instance): void {
            $ref = new \ReflectionProperty(self::class, $prop);
            $ref->setValue($instance, $value);
        };

        $setClosed('baseUrl', $baseUrl);
        $setClosed('httpClient', $httpClient);
        $setClosed('requestFactory', $requestFactory);
        $setClosed('streamFactory', $streamFactory);
        $setClosed('logger', new NullLogger());

        return $instance;
    }

    public function initiateDeviceLinkAuthentication(
        DeviceLinkAuthenticationRequest $request,
    ): DeviceLinkAuthenticationResponse {
        $url = $this->baseUrl . ApiEndpoints::DEVICE_LINK_ANONYMOUS_AUTHENTICATION;
        $response = $this->postRequest($url, $request->toArray());

        return DeviceLinkAuthenticationResponse::fromArray($response);
    }

    public function initiateNotificationAuthentication(
        NotificationAuthenticationRequest $request,
        ?string $documentNumber = null,
        ?string $semanticsIdentifier = null,
    ): NotificationAuthenticationResponse {
        if ($documentNumber !== null) {
            $endpoint = sprintf(ApiEndpoints::NOTIFICATION_AUTHENTICATION_BY_DOCUMENT_NUMBER, $documentNumber);
        } elseif ($semanticsIdentifier !== null) {
            $endpoint = sprintf(ApiEndpoints::NOTIFICATION_AUTHENTICATION_BY_SEMANTICS_IDENTIFIER, $semanticsIdentifier);
        } else {
            throw new \InvalidArgumentException('Either documentNumber or semanticsIdentifier must be provided');
        }

        $url = $this->baseUrl . $endpoint;
        $response = $this->postRequest($url, $request->toArray());

        return NotificationAuthenticationResponse::fromArray($response);
    }

    public function getSessionStatus(string $sessionId, ?int $timeoutMs = null): SessionStatus
    {
        $endpoint = sprintf(ApiEndpoints::SESSION_STATUS, $sessionId);
        $url = $this->baseUrl . $endpoint;

        if ($timeoutMs !== null) {
            $url .= '?timeoutMs=' . $timeoutMs;
        }

        $response = $this->getRequest($url);

        return SessionStatus::fromArray($response);
    }

    /**
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     * @throws SmartIdException
     */
    private function postRequest(string $url, array $body): array
    {
        $this->logger->debug('Sending POST request', ['url' => $url]);

        $request = $this->requestFactory->createRequest('POST', $url)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streamFactory->createStream(json_encode($body, JSON_THROW_ON_ERROR)));

        $response = $this->httpClient->sendRequest($request);

        return $this->handleResponse($response, $url);
    }

    /**
     * @return array<string, mixed>
     * @throws SmartIdException
     */
    private function getRequest(string $url): array
    {
        $this->logger->debug('Sending GET request', ['url' => $url]);

        $request = $this->requestFactory->createRequest('GET', $url)
            ->withHeader('Accept', 'application/json');

        $response = $this->httpClient->sendRequest($request);

        return $this->handleResponse($response, $url);
    }

    /**
     * @return array<string, mixed>
     * @throws SmartIdException
     */
    private function handleResponse(ResponseInterface $response, string $url): array
    {
        $statusCode = $response->getStatusCode();
        $contents = $response->getBody()->getContents();

        if ($statusCode >= 200 && $statusCode < 300) {
            $this->logger->debug('Received successful response', ['url' => $url, 'statusCode' => $statusCode]);
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

            return $decoded;
        }

        $this->logger->warning('Received error response', ['url' => $url, 'statusCode' => $statusCode]);
        $this->throwExceptionForStatusCode($statusCode, $url, $contents);
    }

    /**
     * @throws SmartIdException
     * @return never
     */
    private function throwExceptionForStatusCode(int $statusCode, string $url, string $contents): never
    {
        $message = $this->extractErrorMessage($contents);

        match ($statusCode) {
            400 => throw new InvalidParametersException(
                "Invalid request parameters: {$message}",
            ),
            401 => throw new UnauthorizedException(
                'Authentication failed - invalid Relying Party credentials',
            ),
            403 => throw new UnauthorizedException(
                "Forbidden - user not found or certificate level mismatch: {$message}",
            ),
            404 => throw new SessionNotFoundException(
                "Session not found for URL: {$url}",
            ),
            471 => throw new UserAccountException(
                'No suitable account of requested type found for the user',
                UserAccountException::NO_SUITABLE_ACCOUNT,
            ),
            472 => throw new UserAccountException(
                'Person should view Smart-ID app or Smart-ID self-service portal',
                UserAccountException::PERSON_SHOULD_VIEW_APP,
            ),
            480 => throw new UserAccountException(
                'Client-side API is too old and not supported anymore',
                UserAccountException::CLIENT_TOO_OLD,
            ),
            500, 502, 503, 504 => throw new SmartIdException(
                "Smart-ID service temporarily unavailable: HTTP {$statusCode}",
            ),
            580 => throw new UnderMaintenanceException(
                'System is under maintenance, retry again later.',
            ),
            default => throw new SmartIdException(
                "Smart-ID API error: HTTP {$statusCode} for URL: {$url}. Response: {$contents}",
            ),
        };
    }

    private function extractErrorMessage(string $contents): string
    {
        if (empty($contents)) {
            return 'No details provided';
        }

        try {
            $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

            if (is_array($data)) {
                $message = $data['message'] ?? $data['error'] ?? null;

                return is_string($message) ? $message : $contents;
            }

            return $contents;
        } catch (\JsonException) {
            return $contents;
        }
    }
}
