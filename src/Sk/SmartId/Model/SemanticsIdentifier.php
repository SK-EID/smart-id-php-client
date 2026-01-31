<?php

declare(strict_types=1);

namespace Sk\SmartId\Model;

class SemanticsIdentifier
{
    public function __construct(
        private readonly string $type,
        private readonly string $countryCode,
        private readonly string $identifier,
    ) {
    }

    public static function fromString(string $semanticsIdentifier): self
    {
        if (!preg_match('/^(PNO|PAS|IDC)([A-Z]{2})-(.+)$/', $semanticsIdentifier, $matches)) {
            throw new \InvalidArgumentException('Invalid semantics identifier format: ' . $semanticsIdentifier);
        }

        return new self($matches[1], $matches[2], $matches[3]);
    }

    public static function forPerson(string $countryCode, string $nationalIdentityNumber): self
    {
        return new self('PNO', strtoupper($countryCode), $nationalIdentityNumber);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function __toString(): string
    {
        return $this->type . $this->countryCode . '-' . $this->identifier;
    }
}
