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

namespace Sk\SmartId\Tests\Util;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\Util\CallbackUrlValidator;

class CallbackUrlValidatorTest extends TestCase
{
    #[Test]
    public function validateReturnsTrueForHttpsUrl(): void
    {
        $this->assertTrue(CallbackUrlValidator::validate('https://example.com/callback'));
    }

    #[Test]
    public function validateReturnsTrueForHttpUrl(): void
    {
        $this->assertTrue(CallbackUrlValidator::validate('http://example.com/callback'));
    }

    #[Test]
    public function validateReturnsTrueForUrlWithPort(): void
    {
        $this->assertTrue(CallbackUrlValidator::validate('https://example.com:8443/callback'));
    }

    #[Test]
    public function validateReturnsTrueForUrlWithQueryParams(): void
    {
        $this->assertTrue(CallbackUrlValidator::validate('https://example.com/callback?param=value'));
    }

    #[Test]
    public function validateReturnsFalseForFtpScheme(): void
    {
        $this->assertFalse(CallbackUrlValidator::validate('ftp://example.com/file'));
    }

    #[Test]
    public function validateReturnsFalseForFileScheme(): void
    {
        $this->assertFalse(CallbackUrlValidator::validate('file:///etc/passwd'));
    }

    #[Test]
    public function validateReturnsFalseForMissingScheme(): void
    {
        $this->assertFalse(CallbackUrlValidator::validate('example.com/callback'));
    }

    #[Test]
    public function validateReturnsFalseForMissingHost(): void
    {
        $this->assertFalse(CallbackUrlValidator::validate('https:///callback'));
    }

    #[Test]
    public function validateReturnsFalseForInvalidUrl(): void
    {
        $this->assertFalse(CallbackUrlValidator::validate('not a valid url'));
    }

    #[Test]
    public function validateOrThrowDoesNotThrowForValidUrl(): void
    {
        CallbackUrlValidator::validateOrThrow('https://example.com/callback');
        $this->assertTrue(true);
    }

    #[Test]
    public function validateOrThrowThrowsForInvalidUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid callback URL');

        CallbackUrlValidator::validateOrThrow('not a valid url');
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function urlProvider(): array
    {
        return [
            'https with path' => ['https://example.com/path', true],
            'http with path' => ['http://example.com/path', true],
            'https with subdomain' => ['https://api.example.com', true],
            'localhost https' => ['https://localhost:8443', true],
            'ip address' => ['https://192.168.1.1/callback', true],
            'empty string' => ['', false],
            'just path' => ['/callback', false],
            'javascript scheme' => ['javascript:alert(1)', false],
            'data scheme' => ['data:text/html,<h1>test</h1>', false],
        ];
    }

    #[Test]
    #[DataProvider('urlProvider')]
    public function validateWithVariousUrls(string $url, bool $expected): void
    {
        $this->assertSame($expected, CallbackUrlValidator::validate($url));
    }

    #[Test]
    public function validateWithRequireHttpsReturnsFalseForHttp(): void
    {
        $this->assertFalse(CallbackUrlValidator::validate('http://example.com/callback', true));
    }

    #[Test]
    public function validateWithRequireHttpsReturnsTrueForHttps(): void
    {
        $this->assertTrue(CallbackUrlValidator::validate('https://example.com/callback', true));
    }

    #[Test]
    public function isHttpsReturnsTrueForHttpsUrl(): void
    {
        $this->assertTrue(CallbackUrlValidator::isHttps('https://example.com/callback'));
    }

    #[Test]
    public function isHttpsReturnsFalseForHttpUrl(): void
    {
        $this->assertFalse(CallbackUrlValidator::isHttps('http://example.com/callback'));
    }

    #[Test]
    public function isHttpsReturnsFalseForInvalidUrl(): void
    {
        $this->assertFalse(CallbackUrlValidator::isHttps('not a valid url'));
    }

    #[Test]
    public function validateOrThrowWithRequireHttpsThrowsForHttp(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid callback URL (HTTPS required)');

        CallbackUrlValidator::validateOrThrow('http://example.com/callback', requireHttps: true);
    }

    #[Test]
    public function validateSessionSecretDigestReturnsTrueForValidDigest(): void
    {
        $sessionSecret = base64_encode('test-session-secret');
        $hash = hash('sha256', base64_decode($sessionSecret), true);
        $digest = rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');

        $this->assertTrue(CallbackUrlValidator::validateSessionSecretDigest($digest, $sessionSecret));
    }

    #[Test]
    public function validateSessionSecretDigestReturnsFalseForWrongDigest(): void
    {
        $sessionSecret = base64_encode('test-session-secret');

        $this->assertFalse(CallbackUrlValidator::validateSessionSecretDigest('wrong-digest', $sessionSecret));
    }

    #[Test]
    public function validateSessionSecretDigestReturnsFalseForInvalidBase64Secret(): void
    {
        $this->assertFalse(CallbackUrlValidator::validateSessionSecretDigest('some-digest', '!!!not-base64!!!'));
    }

    #[Test]
    public function validateUserChallengeVerifierReturnsTrueForValidVerifier(): void
    {
        $verifier = 'test-verifier-value';
        $hash = hash('sha256', $verifier, true);
        $expected = rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');

        $this->assertTrue(CallbackUrlValidator::validateUserChallengeVerifier($verifier, $expected));
    }

    #[Test]
    public function validateUserChallengeVerifierReturnsFalseForWrongVerifier(): void
    {
        $this->assertFalse(CallbackUrlValidator::validateUserChallengeVerifier('verifier', 'wrong-challenge'));
    }

    #[Test]
    public function validateOrThrowWithRequireHttpsDoesNotThrowForHttps(): void
    {
        CallbackUrlValidator::validateOrThrow('https://example.com/callback', true);
        $this->assertTrue(true);
    }
}
