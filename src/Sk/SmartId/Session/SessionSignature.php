<?php

declare(strict_types=1);

namespace Sk\SmartId\Session;

use Sk\SmartId\Exception\ValidationException;

class SessionSignature
{
    /**
     * @param array<string, string>|null $signatureAlgorithmParameters
     */
    public function __construct(
        private readonly string $value,
        private readonly string $signatureAlgorithm,
        private readonly ?string $serverRandom = null,
        private readonly ?string $userChallenge = null,
        private readonly ?string $flowType = null,
        private readonly ?array $signatureAlgorithmParameters = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['value'],
            $data['signatureAlgorithm'],
            $data['serverRandom'] ?? null,
            $data['userChallenge'] ?? null,
            $data['flowType'] ?? null,
            $data['signatureAlgorithmParameters'] ?? null,
        );
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getSignatureAlgorithm(): string
    {
        return $this->signatureAlgorithm;
    }

    public function getServerRandom(): ?string
    {
        return $this->serverRandom;
    }

    public function getUserChallenge(): ?string
    {
        return $this->userChallenge;
    }

    public function getFlowType(): ?string
    {
        return $this->flowType;
    }

    /**
     * @return array<string, string>|null
     */
    public function getSignatureAlgorithmParameters(): ?array
    {
        return $this->signatureAlgorithmParameters;
    }

    public function getDecodedValue(): string
    {
        $decoded = base64_decode($this->value, true);
        if ($decoded === false) {
            throw new ValidationException('Invalid base64 encoded signature value');
        }

        return $decoded;
    }
}
