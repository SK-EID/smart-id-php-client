<?php

declare(strict_types=1);

namespace Sk\SmartId\Tests\Notification;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\Notification\NotificationAuthenticationResponse;
use Sk\SmartId\Notification\NotificationAuthenticationSession;

class NotificationAuthenticationSessionTest extends TestCase
{
    #[Test]
    public function getSessionIdReturnsCorrectValue(): void
    {
        $response = new NotificationAuthenticationResponse('session-123');
        $session = new NotificationAuthenticationSession(
            $response,
            'rpChallenge',
            '1234',
        );

        $this->assertSame('session-123', $session->getSessionId());
    }

    #[Test]
    public function getVerificationCodeReturnsCorrectValue(): void
    {
        $response = new NotificationAuthenticationResponse('session-123');
        $session = new NotificationAuthenticationSession(
            $response,
            'rpChallenge',
            '5678',
        );

        $this->assertSame('5678', $session->getVerificationCode());
    }

    #[Test]
    public function getRpChallengeReturnsCorrectValue(): void
    {
        $response = new NotificationAuthenticationResponse('session-123');
        $rpChallenge = base64_encode('test-challenge');
        $session = new NotificationAuthenticationSession(
            $response,
            $rpChallenge,
            '1234',
        );

        $this->assertSame($rpChallenge, $session->getRpChallenge());
    }

    #[Test]
    public function getResponseReturnsCorrectValue(): void
    {
        $response = new NotificationAuthenticationResponse('session-123');
        $session = new NotificationAuthenticationSession(
            $response,
            'rpChallenge',
            '1234',
        );

        $this->assertSame($response, $session->getResponse());
    }
}
