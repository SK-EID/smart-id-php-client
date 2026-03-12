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

namespace Sk\SmartId\Tests\Notification;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\Api\SmartIdConnector;
use Sk\SmartId\Enum\CertificateLevel;
use Sk\SmartId\Enum\HashAlgorithm;
use Sk\SmartId\Notification\NotificationInteraction;
use Sk\SmartId\Model\SemanticsIdentifier;
use Sk\SmartId\Notification\NotificationAuthenticationRequest;
use Sk\SmartId\Notification\NotificationAuthenticationRequestBuilder;
use Sk\SmartId\Notification\NotificationAuthenticationResponse;
use Sk\SmartId\Notification\NotificationAuthenticationSession;

class NotificationAuthenticationRequestBuilderTest extends TestCase
{
    private SmartIdConnector $connector;

    protected function setUp(): void
    {
        $this->connector = $this->createMock(SmartIdConnector::class);
    }

    #[Test]
    public function initiateWithDocumentNumberCreatesSession(): void
    {
        $response = new NotificationAuthenticationResponse('session-123');

        $this->connector->expects($this->once())
            ->method('initiateNotificationAuthentication')
            ->with(
                $this->isInstanceOf(NotificationAuthenticationRequest::class),
                'PNOEE-12345678901',
                null,
            )
            ->willReturn($response);

        $builder = new NotificationAuthenticationRequestBuilder(
            $this->connector,
            'rp-uuid',
            'Test RP',
        );

        $session = $builder
            ->withDocumentNumber('PNOEE-12345678901')
            ->withAllowedInteractionsOrder([NotificationInteraction::displayTextAndPin('Test')])
            ->initiate();

        $this->assertInstanceOf(NotificationAuthenticationSession::class, $session);
        $this->assertSame('session-123', $session->getSessionId());
    }

    #[Test]
    public function initiateWithSemanticsIdentifierCreatesSession(): void
    {
        $response = new NotificationAuthenticationResponse('session-123');
        $semanticsId = SemanticsIdentifier::forPerson('EE', '12345678901');

        $this->connector->expects($this->once())
            ->method('initiateNotificationAuthentication')
            ->with(
                $this->isInstanceOf(NotificationAuthenticationRequest::class),
                null,
                'PNOEE-12345678901',
            )
            ->willReturn($response);

        $builder = new NotificationAuthenticationRequestBuilder(
            $this->connector,
            'rp-uuid',
            'Test RP',
        );

        $session = $builder
            ->withSemanticsIdentifier($semanticsId)
            ->withAllowedInteractionsOrder([NotificationInteraction::displayTextAndPin('Test')])
            ->initiate();

        $this->assertInstanceOf(NotificationAuthenticationSession::class, $session);
    }

    #[Test]
    public function initiateThrowsWhenNeitherDocumentNumberNorSemanticsIdentifierSet(): void
    {
        $builder = new NotificationAuthenticationRequestBuilder(
            $this->connector,
            'rp-uuid',
            'Test RP',
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Either documentNumber or semanticsIdentifier must be set');

        $builder->initiate();
    }

    #[Test]
    public function withDocumentNumberClearsSemanticsIdentifier(): void
    {
        $response = new NotificationAuthenticationResponse('session-123');
        $semanticsId = SemanticsIdentifier::forPerson('EE', '99999999999');

        $this->connector->expects($this->once())
            ->method('initiateNotificationAuthentication')
            ->with(
                $this->isInstanceOf(NotificationAuthenticationRequest::class),
                'PNOEE-12345678901',
                null,
            )
            ->willReturn($response);

        $builder = new NotificationAuthenticationRequestBuilder(
            $this->connector,
            'rp-uuid',
            'Test RP',
        );

        $builder
            ->withSemanticsIdentifier($semanticsId)
            ->withDocumentNumber('PNOEE-12345678901')
            ->withAllowedInteractionsOrder([NotificationInteraction::displayTextAndPin('Test')])
            ->initiate();
    }

    #[Test]
    public function withSemanticsIdentifierClearsDocumentNumber(): void
    {
        $response = new NotificationAuthenticationResponse('session-123');
        $semanticsId = SemanticsIdentifier::forPerson('EE', '12345678901');

        $this->connector->expects($this->once())
            ->method('initiateNotificationAuthentication')
            ->with(
                $this->isInstanceOf(NotificationAuthenticationRequest::class),
                null,
                'PNOEE-12345678901',
            )
            ->willReturn($response);

        $builder = new NotificationAuthenticationRequestBuilder(
            $this->connector,
            'rp-uuid',
            'Test RP',
        );

        $builder
            ->withDocumentNumber('SOME-DOC-NUMBER')
            ->withSemanticsIdentifier($semanticsId)
            ->withAllowedInteractionsOrder([NotificationInteraction::displayTextAndPin('Test')])
            ->initiate();
    }

    #[Test]
    public function withRpChallengeUsesProvidedChallenge(): void
    {
        $providedChallenge = base64_encode('my-custom-challenge');
        $response = new NotificationAuthenticationResponse('session-123');

        $capturedRequest = null;
        $this->connector->method('initiateNotificationAuthentication')
            ->willReturnCallback(function (NotificationAuthenticationRequest $request) use ($response, &$capturedRequest) {
                $capturedRequest = $request;

                return $response;
            });

        $builder = new NotificationAuthenticationRequestBuilder(
            $this->connector,
            'rp-uuid',
            'Test RP',
        );

        $session = $builder
            ->withDocumentNumber('DOC123')
            ->withRpChallenge($providedChallenge)
            ->withAllowedInteractionsOrder([NotificationInteraction::displayTextAndPin('Test')])
            ->initiate();

        $this->assertSame($providedChallenge, $capturedRequest->getRpChallenge());
        $this->assertSame($providedChallenge, $session->getRpChallenge());
    }

    #[Test]
    public function withHashAlgorithmUsesProvidedAlgorithm(): void
    {
        $response = new NotificationAuthenticationResponse('session-123');

        $capturedRequest = null;
        $this->connector->method('initiateNotificationAuthentication')
            ->willReturnCallback(function (NotificationAuthenticationRequest $request) use ($response, &$capturedRequest) {
                $capturedRequest = $request;

                return $response;
            });

        $builder = new NotificationAuthenticationRequestBuilder(
            $this->connector,
            'rp-uuid',
            'Test RP',
        );

        $builder
            ->withDocumentNumber('DOC123')
            ->withHashAlgorithm(HashAlgorithm::SHA256)
            ->withAllowedInteractionsOrder([NotificationInteraction::displayTextAndPin('Test')])
            ->initiate();

        $this->assertSame(HashAlgorithm::SHA256, $capturedRequest->getHashAlgorithm());
    }

    #[Test]
    public function withCertificateLevelUsesProvidedLevel(): void
    {
        $response = new NotificationAuthenticationResponse('session-123');

        $capturedRequest = null;
        $this->connector->method('initiateNotificationAuthentication')
            ->willReturnCallback(function (NotificationAuthenticationRequest $request) use ($response, &$capturedRequest) {
                $capturedRequest = $request;

                return $response;
            });

        $builder = new NotificationAuthenticationRequestBuilder(
            $this->connector,
            'rp-uuid',
            'Test RP',
        );

        $builder
            ->withDocumentNumber('DOC123')
            ->withCertificateLevel(CertificateLevel::QUALIFIED)
            ->withAllowedInteractionsOrder([NotificationInteraction::displayTextAndPin('Test')])
            ->initiate();

        $this->assertSame(CertificateLevel::QUALIFIED, $capturedRequest->getCertificateLevel());
    }

    #[Test]
    public function withNonceIncludesNonceInRequest(): void
    {
        $response = new NotificationAuthenticationResponse('session-123');

        $capturedRequest = null;
        $this->connector->method('initiateNotificationAuthentication')
            ->willReturnCallback(function (NotificationAuthenticationRequest $request) use ($response, &$capturedRequest) {
                $capturedRequest = $request;

                return $response;
            });

        $builder = new NotificationAuthenticationRequestBuilder(
            $this->connector,
            'rp-uuid',
            'Test RP',
        );

        $builder
            ->withDocumentNumber('DOC123')
            ->withNonce('test-nonce')
            ->withAllowedInteractionsOrder([NotificationInteraction::displayTextAndPin('Test')])
            ->initiate();

        $array = $capturedRequest->toArray();
        $this->assertSame('test-nonce', $array['nonce']);
    }

    #[Test]
    public function withCapabilitiesIncludesCapabilitiesInRequest(): void
    {
        $response = new NotificationAuthenticationResponse('session-123');

        $capturedRequest = null;
        $this->connector->method('initiateNotificationAuthentication')
            ->willReturnCallback(function (NotificationAuthenticationRequest $request) use ($response, &$capturedRequest) {
                $capturedRequest = $request;

                return $response;
            });

        $builder = new NotificationAuthenticationRequestBuilder(
            $this->connector,
            'rp-uuid',
            'Test RP',
        );

        $builder
            ->withDocumentNumber('DOC123')
            ->withCapabilities(['ADVANCED'])
            ->withAllowedInteractionsOrder([NotificationInteraction::displayTextAndPin('Test')])
            ->initiate();

        $array = $capturedRequest->toArray();
        $this->assertSame(['ADVANCED'], $array['capabilities']);
    }

    #[Test]
    public function withAllowedInteractionsOrderUsesProvidedInteractions(): void
    {
        $response = new NotificationAuthenticationResponse('session-123');

        $interactions = [
            NotificationInteraction::displayTextAndPin('Please confirm'),
            NotificationInteraction::confirmationMessage('Confirm login'),
        ];

        $capturedRequest = null;
        $this->connector->method('initiateNotificationAuthentication')
            ->willReturnCallback(function (NotificationAuthenticationRequest $request) use ($response, &$capturedRequest) {
                $capturedRequest = $request;

                return $response;
            });

        $builder = new NotificationAuthenticationRequestBuilder(
            $this->connector,
            'rp-uuid',
            'Test RP',
        );

        $builder
            ->withDocumentNumber('DOC123')
            ->withAllowedInteractionsOrder($interactions)
            ->initiate();

        $this->assertSame($interactions, $capturedRequest->getAllowedInteractionsOrder());
    }

    #[Test]
    public function initiateThrowsWhenNoInteractionsProvided(): void
    {
        $builder = new NotificationAuthenticationRequestBuilder(
            $this->connector,
            'rp-uuid',
            'Test RP',
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one interaction must be set');

        $builder->withDocumentNumber('DOC123')->initiate();
    }

    #[Test]
    public function initiateGeneratesVerificationCode(): void
    {
        $response = new NotificationAuthenticationResponse('session-123');

        $this->connector->method('initiateNotificationAuthentication')->willReturn($response);

        $builder = new NotificationAuthenticationRequestBuilder(
            $this->connector,
            'rp-uuid',
            'Test RP',
        );

        $session = $builder
            ->withDocumentNumber('DOC123')
            ->withAllowedInteractionsOrder([NotificationInteraction::displayTextAndPin('Test')])
            ->initiate();

        $this->assertSame(4, strlen($session->getVerificationCode()));
        $this->assertMatchesRegularExpression('/^\d{4}$/', $session->getVerificationCode());
    }

    #[Test]
    public function builderMethodsReturnSelf(): void
    {
        $builder = new NotificationAuthenticationRequestBuilder(
            $this->connector,
            'rp-uuid',
            'Test RP',
        );

        $this->assertSame($builder, $builder->withDocumentNumber('DOC'));
        $this->assertSame($builder, $builder->withSemanticsIdentifier(SemanticsIdentifier::forPerson('EE', '123')));
        $this->assertSame($builder, $builder->withRpChallenge('challenge'));
        $this->assertSame($builder, $builder->withHashAlgorithm(HashAlgorithm::SHA512));
        $this->assertSame($builder, $builder->withCertificateLevel(CertificateLevel::QUALIFIED));
        $this->assertSame($builder, $builder->withNonce('nonce'));
        $this->assertSame($builder, $builder->withCapabilities([]));
        $this->assertSame($builder, $builder->withAllowedInteractionsOrder([]));
    }
}
