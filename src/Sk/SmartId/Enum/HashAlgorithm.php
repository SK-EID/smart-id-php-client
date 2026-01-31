<?php

declare(strict_types=1);

namespace Sk\SmartId\Enum;

enum HashAlgorithm: string
{
    case SHA256 = 'SHA-256';
    case SHA384 = 'SHA-384';
    case SHA512 = 'SHA-512';

    public function getDigestAlgorithm(): string
    {
        return match ($this) {
            self::SHA256 => 'sha256',
            self::SHA384 => 'sha384',
            self::SHA512 => 'sha512',
        };
    }

    public function getHashLength(): int
    {
        return match ($this) {
            self::SHA256 => 32,
            self::SHA384 => 48,
            self::SHA512 => 64,
        };
    }
}
