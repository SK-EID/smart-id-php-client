<?php

declare(strict_types=1);

namespace Sk\SmartId\Util;

class CallbackUrlValidator
{
    public static function validate(string $url, bool $requireHttps = false): bool
    {
        $parsed = parse_url($url);

        if ($parsed === false || !isset($parsed['scheme']) || !isset($parsed['host'])) {
            return false;
        }

        $allowedSchemes = $requireHttps ? ['https'] : ['http', 'https'];

        if (!in_array($parsed['scheme'], $allowedSchemes, true)) {
            return false;
        }

        return true;
    }

    public static function isHttps(string $url): bool
    {
        $parsed = parse_url($url);

        return $parsed !== false && isset($parsed['scheme']) && $parsed['scheme'] === 'https';
    }

    public static function validateOrThrow(string $url, bool $requireHttps = false): void
    {
        if (!self::validate($url, $requireHttps)) {
            $message = $requireHttps
                ? 'Invalid callback URL (HTTPS required): '
                : 'Invalid callback URL: ';
            throw new \InvalidArgumentException($message . $url);
        }
    }
}
