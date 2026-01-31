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

namespace Sk\SmartId\Tests\DeviceLink;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\Api\SmartIdConnector;
use Sk\SmartId\DeviceLink\DeviceLinkAuthenticationRequest;
use Sk\SmartId\DeviceLink\DeviceLinkAuthenticationRequestBuilder;
use Sk\SmartId\DeviceLink\DeviceLinkAuthenticationResponse;
use Sk\SmartId\DeviceLink\DeviceLinkAuthenticationSession;
use Sk\SmartId\Enum\CertificateLevel;
use Sk\SmartId\Enum\HashAlgorithm;
use Sk\SmartId\Model\Interaction;

class DeviceLinkAuthenticationRequestBuilderTest extends TestCase
{
    private SmartIdConnector $connector;

    protected function setUp(): void
    {
        $this->connector = $this->createMock(SmartIdConnector::class);
    }

    #[Test]
    public function initiateCreatesSession(): void
    {
        $response = new DeviceLinkAuthenticationResponse(
            'session-123',
            'token-456',
            base64_encode('test-secret-32-bytes-long-string'),
            'https://sid.demo.sk.ee/v3/device',
        );

        $this->connector->expects($this->once())
            ->method('initiateDeviceLinkAuthentication')
            ->willReturn($response);

        $builder = new DeviceLinkAuthenticationRequestBuilder(
            $this->connector,
            'rp-uuid',
            'Test RP',
        );

        $session = $builder->initiate();

        $this->assertInstanceOf(DeviceLinkAuthenticationSession::class, $session);
        $this->assertSame('session-123', $session->getSessionId());
        $this->assertSame(4, strlen($session->getVerificationCode()));
    }

    #[Test]
    public function withRpChallengeUsesProvidedChallenge(): void
    {
        $providedChallenge = base64_encode('my-custom-challenge-32-bytes!!!');
        $response = new DeviceLinkAuthenticationResponse(
            'session-123',
            'token-456',
            base64_encode('secret'),
            'https://example.com',
        );

        $capturedRequest = null;
        $this->connector->method('initiateDeviceLinkAuthentication')
            ->willReturnCallback(function (DeviceLinkAuthenticationRequest $request) use ($response, &$capturedRequest) {
                $capturedRequest = $request;

                return $response;
            });

        $builder = new DeviceLinkAuthenticationRequestBuilder(
            $this->connector,
            'rp-uuid',
            'Test RP',
        );

        $session = $builder->withRpChallenge($providedChallenge)->initiate();

        $this->assertSame($providedChallenge, $capturedRequest->getRpChallenge());
        $this->assertSame($providedChallenge, $session->getRpChallenge());
    }

    #[Test]
    public function withHashAlgorithmUsesProvidedAlgorithm(): void
    {
        $response = new DeviceLinkAuthenticationResponse(
            'session-123',
            'token',
            base64_encode('secret'),
            'https://example.com',
        );

        $capturedRequest = null;
        $this->connector->method('initiateDeviceLinkAuthentication')
            ->willReturnCallback(function (DeviceLinkAuthenticationRequest $request) use ($response, &$capturedRequest) {
                $capturedRequest = $request;

                return $response;
            });

        $builder = new DeviceLinkAuthenticationRequestBuilder(
            $this->connector,
            'rp-uuid',
            'Test RP',
        );

        $builder->withHashAlgorithm(HashAlgorithm::SHA256)->initiate();

        $this->assertSame(HashAlgorithm::SHA256, $capturedRequest->getHashAlgorithm());
    }

    #[Test]
    public function withCertificateLevelUsesProvidedLevel(): void
    {
        $response = new DeviceLinkAuthenticationResponse(
            'session-123',
            'token',
            base64_encode('secret'),
            'https://example.com',
        );

        $capturedRequest = null;
        $this->connector->method('initiateDeviceLinkAuthentication')
            ->willReturnCallback(function (DeviceLinkAuthenticationRequest $request) use ($response, &$capturedRequest) {
                $capturedRequest = $request;

                return $response;
            });

        $builder = new DeviceLinkAuthenticationRequestBuilder(
            $this->connector,
            'rp-uuid',
            'Test RP',
        );

        $builder->withCertificateLevel(CertificateLevel::QUALIFIED)->initiate();

        $this->assertSame(CertificateLevel::QUALIFIED, $capturedRequest->getCertificateLevel());
    }

    #[Test]
    public function withNonceIncludesNonceInRequest(): void
    {
        $response = new DeviceLinkAuthenticationResponse(
            'session-123',
            'token',
            base64_encode('secret'),
            'https://example.com',
        );

        $capturedRequest = null;
        $this->connector->method('initiateDeviceLinkAuthentication')
            ->willReturnCallback(function (DeviceLinkAuthenticationRequest $request) use ($response, &$capturedRequest) {
                $capturedRequest = $request;

                return $response;
            });

        $builder = new DeviceLinkAuthenticationRequestBuilder(
            $this->connector,
            'rp-uuid',
            'Test RP',
        );

        $builder->withNonce('test-nonce')->initiate();

        $array = $capturedRequest->toArray();
        $this->assertSame('test-nonce', $array['nonce']);
    }

    #[Test]
    public function withCapabilitiesIncludesCapabilitiesInRequest(): void
    {
        $response = new DeviceLinkAuthenticationResponse(
            'session-123',
            'token',
            base64_encode('secret'),
            'https://example.com',
        );

        $capturedRequest = null;
        $this->connector->method('initiateDeviceLinkAuthentication')
            ->willReturnCallback(function (DeviceLinkAuthenticationRequest $request) use ($response, &$capturedRequest) {
                $capturedRequest = $request;

                return $response;
            });

        $builder = new DeviceLinkAuthenticationRequestBuilder(
            $this->connector,
            'rp-uuid',
            'Test RP',
        );

        $builder->withCapabilities(['ADVANCED'])->initiate();

        $array = $capturedRequest->toArray();
        $this->assertSame(['ADVANCED'], $array['capabilities']);
    }

    #[Test]
    public function withAllowedInteractionsOrderUsesProvidedInteractions(): void
    {
        $response = new DeviceLinkAuthenticationResponse(
            'session-123',
            'token',
            base64_encode('secret'),
            'https://example.com',
        );

        $interactions = [
            Interaction::displayTextAndPin('Please confirm'),
            Interaction::verificationCodeChoice(),
        ];

        $capturedRequest = null;
        $this->connector->method('initiateDeviceLinkAuthentication')
            ->willReturnCallback(function (DeviceLinkAuthenticationRequest $request) use ($response, &$capturedRequest) {
                $capturedRequest = $request;

                return $response;
            });

        $builder = new DeviceLinkAuthenticationRequestBuilder(
            $this->connector,
            'rp-uuid',
            'Test RP',
        );

        $builder->withAllowedInteractionsOrder($interactions)->initiate();

        $this->assertSame($interactions, $capturedRequest->getAllowedInteractionsOrder());
    }

    #[Test]
    public function initiateUsesDefaultInteractionsWhenNotProvided(): void
    {
        $response = new DeviceLinkAuthenticationResponse(
            'session-123',
            'token',
            base64_encode('secret'),
            'https://example.com',
        );

        $capturedRequest = null;
        $this->connector->method('initiateDeviceLinkAuthentication')
            ->willReturnCallback(function (DeviceLinkAuthenticationRequest $request) use ($response, &$capturedRequest) {
                $capturedRequest = $request;

                return $response;
            });

        $builder = new DeviceLinkAuthenticationRequestBuilder(
            $this->connector,
            'rp-uuid',
            'Test RP',
        );

        $builder->initiate();

        $interactions = $capturedRequest->getAllowedInteractionsOrder();
        $this->assertCount(1, $interactions);
    }

    #[Test]
    public function initiateGeneratesRpChallengeWhenNotProvided(): void
    {
        $response = new DeviceLinkAuthenticationResponse(
            'session-123',
            'token',
            base64_encode('secret'),
            'https://example.com',
        );

        $capturedRequest = null;
        $this->connector->method('initiateDeviceLinkAuthentication')
            ->willReturnCallback(function (DeviceLinkAuthenticationRequest $request) use ($response, &$capturedRequest) {
                $capturedRequest = $request;

                return $response;
            });

        $builder = new DeviceLinkAuthenticationRequestBuilder(
            $this->connector,
            'rp-uuid',
            'Test RP',
        );

        $builder->initiate();

        $this->assertNotEmpty($capturedRequest->getRpChallenge());
        $decoded = base64_decode($capturedRequest->getRpChallenge(), true);
        $this->assertNotFalse($decoded);
    }

    #[Test]
    public function builderMethodsReturnSelf(): void
    {
        $builder = new DeviceLinkAuthenticationRequestBuilder(
            $this->connector,
            'rp-uuid',
            'Test RP',
        );

        $this->assertSame($builder, $builder->withRpChallenge('challenge'));
        $this->assertSame($builder, $builder->withHashAlgorithm(HashAlgorithm::SHA512));
        $this->assertSame($builder, $builder->withCertificateLevel(CertificateLevel::QUALIFIED));
        $this->assertSame($builder, $builder->withNonce('nonce'));
        $this->assertSame($builder, $builder->withCapabilities([]));
        $this->assertSame($builder, $builder->withAllowedInteractionsOrder([]));
    }
}
