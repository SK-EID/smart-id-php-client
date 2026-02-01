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

    /**
     * Validates that the sessionSecretDigest from callback matches the session secret.
     *
     * The Smart-ID app calculates SHA-256 hash of the sessionSecret and sends it as
     * sessionSecretDigest parameter. This proves the callback came from Smart-ID.
     *
     * @param string $sessionSecretDigest Base64url-encoded digest from callback URL
     * @param string $sessionSecret Base64-encoded session secret from init response
     * @return bool True if digest matches
     */
    public static function validateSessionSecretDigest(string $sessionSecretDigest, string $sessionSecret): bool
    {
        $decodedSecret = base64_decode($sessionSecret, true);
        if ($decodedSecret === false) {
            return false;
        }

        $calculatedDigest = hash('sha256', $decodedSecret, true);
        $calculatedDigestBase64Url = self::base64UrlEncode($calculatedDigest);

        return hash_equals($calculatedDigestBase64Url, $sessionSecretDigest);
    }

    /**
     * Validates userChallengeVerifier against userChallenge from session status.
     *
     * For authentication flows, the Smart-ID app returns userChallengeVerifier which
     * must match the userChallenge in the session status response signature.
     *
     * @param string $userChallengeVerifier From callback URL
     * @param string $userChallenge From session status response (signature.userChallenge)
     * @return bool True if verifier matches challenge
     */
    public static function validateUserChallengeVerifier(string $userChallengeVerifier, string $userChallenge): bool
    {
        return hash_equals($userChallenge, $userChallengeVerifier);
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
