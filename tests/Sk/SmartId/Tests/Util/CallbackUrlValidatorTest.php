<?php

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
}
