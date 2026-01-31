<?php

declare(strict_types=1);

namespace Sk\SmartId\Util;

use Random\RandomException;

class RpChallengeGenerator
{
    private const DEFAULT_CHALLENGE_LENGTH = 32;

    /**
     * @throws RandomException
     */
    public static function generate(int $length = self::DEFAULT_CHALLENGE_LENGTH): string
    {
        return base64_encode(random_bytes($length));
    }
}
