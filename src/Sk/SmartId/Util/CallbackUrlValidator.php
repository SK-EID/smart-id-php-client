<?php

declare(strict_types=1);

namespace Sk\SmartId\Util;

class CallbackUrlValidator
{
    public static function validate(string $url): bool
    {
        $parsed = parse_url($url);

        if ($parsed === false || !isset($parsed['scheme']) || !isset($parsed['host'])) {
            return false;
        }

        if (!in_array($parsed['scheme'], ['http', 'https'], true)) {
            return false;
        }

        return true;
    }

    public static function validateOrThrow(string $url): void
    {
        if (!self::validate($url)) {
            throw new \InvalidArgumentException('Invalid callback URL: ' . $url);
        }
    }
}
