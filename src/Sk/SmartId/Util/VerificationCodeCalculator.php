<?php

declare(strict_types=1);

namespace Sk\SmartId\Util;

use Sk\SmartId\Enum\HashAlgorithm;

class VerificationCodeCalculator
{
    public static function calculate(string $hash): string
    {
        $hash = self::ensureBinaryHash($hash);

        $integralBytes = substr($hash, -2);
        $integralValue = unpack('n', $integralBytes)[1];
        $code = $integralValue % 10000;

        return str_pad((string) $code, 4, '0', STR_PAD_LEFT);
    }

    public static function calculateFromRpChallenge(
        string $rpChallenge,
        HashAlgorithm $algorithm = HashAlgorithm::SHA512,
    ): string {
        $decodedChallenge = base64_decode($rpChallenge, true);
        if ($decodedChallenge === false) {
            throw new \InvalidArgumentException('Invalid base64 encoded rpChallenge');
        }

        $hash = hash($algorithm->getDigestAlgorithm(), $decodedChallenge, true);

        return self::calculate($hash);
    }

    private static function ensureBinaryHash(string $hash): string
    {
        if (strlen($hash) === 64 && ctype_xdigit($hash)) {
            return hex2bin($hash);
        }
        if (strlen($hash) === 96 && ctype_xdigit($hash)) {
            return hex2bin($hash);
        }
        if (strlen($hash) === 128 && ctype_xdigit($hash)) {
            return hex2bin($hash);
        }

        return $hash;
    }
}
