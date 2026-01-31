<?php

declare(strict_types=1);

namespace Sk\SmartId\Notification;

class NotificationAuthenticationSession
{
    public function __construct(
        private readonly NotificationAuthenticationResponse $response,
        private readonly string $rpChallenge,
        private readonly string $verificationCode,
    ) {
    }

    public function getSessionId(): string
    {
        return $this->response->getSessionID();
    }

    public function getVerificationCode(): string
    {
        return $this->verificationCode;
    }

    public function getRpChallenge(): string
    {
        return $this->rpChallenge;
    }

    public function getResponse(): NotificationAuthenticationResponse
    {
        return $this->response;
    }
}
