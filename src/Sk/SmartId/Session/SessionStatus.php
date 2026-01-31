<?php

declare(strict_types=1);

namespace Sk\SmartId\Session;

class SessionStatus
{
    public const STATE_RUNNING = 'RUNNING';

    public const STATE_COMPLETE = 'COMPLETE';

    public function __construct(
        private readonly string $state,
        private readonly ?SessionResult $result = null,
        private readonly ?SessionCertificate $cert = null,
        private readonly ?SessionSignature $signature = null,
        private readonly ?string $deviceIpAddress = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $result = null;
        if (isset($data['result'])) {
            $result = SessionResult::fromArray($data['result']);
        }

        $cert = null;
        if (isset($data['cert'])) {
            $cert = SessionCertificate::fromArray($data['cert']);
        }

        $signature = null;
        if (isset($data['signature'])) {
            $signature = SessionSignature::fromArray($data['signature']);
        }

        return new self(
            $data['state'],
            $result,
            $cert,
            $signature,
            $data['deviceIpAddress'] ?? null,
        );
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getResult(): ?SessionResult
    {
        return $this->result;
    }

    public function isRunning(): bool
    {
        return $this->state === self::STATE_RUNNING;
    }

    public function isComplete(): bool
    {
        return $this->state === self::STATE_COMPLETE;
    }

    public function getCert(): ?SessionCertificate
    {
        return $this->cert;
    }

    public function getSignature(): ?SessionSignature
    {
        return $this->signature;
    }

    public function getDeviceIpAddress(): ?string
    {
        return $this->deviceIpAddress;
    }
}
