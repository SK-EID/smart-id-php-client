<?php

declare(strict_types=1);

namespace Sk\SmartId\Api;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Sk\SmartId\DeviceLink\DeviceLinkAuthenticationRequest;
use Sk\SmartId\DeviceLink\DeviceLinkAuthenticationResponse;
use Sk\SmartId\Exception\SessionNotFoundException;
use Sk\SmartId\Exception\SmartIdException;
use Sk\SmartId\Notification\NotificationAuthenticationRequest;
use Sk\SmartId\Notification\NotificationAuthenticationResponse;
use Sk\SmartId\Session\SessionStatus;

class SmartIdRestConnector implements SmartIdConnector
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {
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
            $endpoint = sprintf(ApiEndpoints::NOTIFICATION_AUTHENTICATION_BY_SEMANTICS_IDENTIFIER, urlencode($semanticsIdentifier));
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

        if ($statusCode === 404) {
            throw new SessionNotFoundException("Session not found for URL: {$url}");
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new SmartIdException("Smart-ID API error: HTTP {$statusCode} for URL: {$url}. Response: {$contents}");
        }

        return json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    }
}
