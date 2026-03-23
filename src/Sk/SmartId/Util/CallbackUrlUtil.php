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

use Sk\SmartId\Exception\ValidationException;

class CallbackUrlUtil
{
    /**
     * Create a callback URL by appending a random URL-safe token as a query parameter.
     *
     * @param string $baseUrl The URL to which the token will be appended
     * @return array{callbackUrl: string, token: string} The full callback URL and the generated token
     */
    public static function createCallbackUrl(string $baseUrl): array
    {
        if ($baseUrl === '') {
            throw new \InvalidArgumentException('baseUrl cannot be empty');
        }

        $token = self::generateUrlSafeToken();
        $separator = str_contains($baseUrl, '?') ? '&' : '?';
        $callbackUrl = $baseUrl . $separator . 'value=' . $token;

        return [
            'callbackUrl' => $callbackUrl,
            'token' => $token,
        ];
    }

    /**
     * Validate that the session secret digest from the callback URL matches
     * the calculated digest of the provided session secret.
     *
     * @param string $sessionSecretDigest The session secret digest received in the callback URL
     * @param string $sessionSecret The original session secret from the session initialization response
     * @throws ValidationException when the session secrets do not match
     */
    public static function validateSessionSecretDigest(string $sessionSecretDigest, string $sessionSecret): void
    {
        if ($sessionSecretDigest === '') {
            throw new \InvalidArgumentException('sessionSecretDigest cannot be empty');
        }
        if ($sessionSecret === '') {
            throw new \InvalidArgumentException('sessionSecret cannot be empty');
        }

        if (!CallbackUrlValidator::validateSessionSecretDigest($sessionSecretDigest, $sessionSecret)) {
            throw new ValidationException(
                'Session secret digest from callback does not match calculated session secret digest',
            );
        }
    }

    private static function generateUrlSafeToken(int $length = 32): string
    {
        return Base64Url::encode(random_bytes($length));
    }
}
