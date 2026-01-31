<?php

declare(strict_types=1);

namespace Sk\SmartId\Session;

class SessionCertificate
{
    public function __construct(
        private readonly string $value,
        private readonly string $certificateLevel,
    ) {
    }

    /**
     * @param array<string, string> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['value'],
            $data['certificateLevel'],
        );
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getCertificateLevel(): string
    {
        return $this->certificateLevel;
    }

    public function getPemEncodedCertificate(): string
    {
        $formatted = chunk_split($this->value, 64, "\n");

        return "-----BEGIN CERTIFICATE-----\n" . $formatted . "-----END CERTIFICATE-----\n";
    }
}
