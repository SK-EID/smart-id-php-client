<?php

declare(strict_types=1);

namespace Sk\SmartId\Notification;

class NotificationAuthenticationResponse
{
    public function __construct(
        private readonly string $sessionID,
    ) {
    }

    /**
     * @param array<string, string> $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data['sessionID']);
    }

    public function getSessionID(): string
    {
        return $this->sessionID;
    }
}
