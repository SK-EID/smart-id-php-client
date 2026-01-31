<?php

declare(strict_types=1);

namespace Sk\SmartId\DeviceLink;

class DeviceLinkAuthenticationResponse
{
    public function __construct(
        private readonly string $sessionID,
        private readonly string $sessionToken,
        private readonly string $sessionSecret,
        private readonly string $deviceLinkBase,
    ) {
    }

    /**
     * @param array<string, string> $data
     * @throws \InvalidArgumentException
     */
    public static function fromArray(array $data): self
    {
        $requiredKeys = ['sessionID', 'sessionToken', 'sessionSecret', 'deviceLinkBase'];
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $data)) {
                throw new \InvalidArgumentException("Missing required key: {$key}");
            }
        }

        return new self(
            $data['sessionID'],
            $data['sessionToken'],
            $data['sessionSecret'],
            $data['deviceLinkBase'],
        );
    }

    public function getSessionID(): string
    {
        return $this->sessionID;
    }

    public function getSessionToken(): string
    {
        return $this->sessionToken;
    }

    public function getSessionSecret(): string
    {
        return $this->sessionSecret;
    }

    public function getDeviceLinkBase(): string
    {
        return $this->deviceLinkBase;
    }
}
