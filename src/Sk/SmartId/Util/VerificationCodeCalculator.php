<?php

/*-
 * #%L
 * Smart ID sample PHP client
 * %%
 * Copyright (C) 2018 - 2026 SK ID Solutions AS
 * %%
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 * #L%
 */

declare(strict_types=1);

namespace Sk\SmartId\Util;

class VerificationCodeCalculator
{
    public static function calculate(string $hash): string
    {
        $hash = self::ensureBinaryHash($hash);

        $integralBytes = substr($hash, -2);
        $unpacked = unpack('n', $integralBytes);
        if ($unpacked === false) {
            throw new \InvalidArgumentException('Failed to unpack hash bytes');
        }
        /** @var int $integralValue */
        $integralValue = $unpacked[1];
        $code = $integralValue % 10000;

        return str_pad((string) $code, 4, '0', STR_PAD_LEFT);
    }

    public static function calculateFromRpChallenge(
        string $rpChallenge,
    ): string {
        $decodedChallenge = base64_decode($rpChallenge, true);
        if ($decodedChallenge === false) {
            throw new \InvalidArgumentException('Invalid base64 encoded rpChallenge');
        }

        // Verification code always uses SHA-256 regardless of signature hash algorithm
        $hash = hash('sha256', $decodedChallenge, true);

        return self::calculate($hash);
    }

    private static function ensureBinaryHash(string $hash): string
    {
        if (strlen($hash) === 64 && ctype_xdigit($hash)) {
            $binary = hex2bin($hash);

            return $binary !== false ? $binary : $hash;
        }
        if (strlen($hash) === 96 && ctype_xdigit($hash)) {
            $binary = hex2bin($hash);

            return $binary !== false ? $binary : $hash;
        }
        if (strlen($hash) === 128 && ctype_xdigit($hash)) {
            $binary = hex2bin($hash);

            return $binary !== false ? $binary : $hash;
        }

        return $hash;
    }
}
