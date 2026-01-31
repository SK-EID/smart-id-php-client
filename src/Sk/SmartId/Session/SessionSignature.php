<?php

declare(strict_types=1);

namespace Sk\SmartId\Session;

class SessionSignature
{
    public function __construct(
        private readonly string $value,
        private readonly string $algorithm,
    ) {
    }

    /**
     * @param array<string, string> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['value'],
            $data['algorithm'],
        );
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }

    public function getDecodedValue(): string
    {
        $decoded = base64_decode($this->value, true);
        if ($decoded === false) {
            throw new \RuntimeException('Invalid base64 encoded signature value');
        }

        return $decoded;
    }
}
