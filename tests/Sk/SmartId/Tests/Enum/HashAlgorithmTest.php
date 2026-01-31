<?php

declare(strict_types=1);

namespace Sk\SmartId\Tests\Enum;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\Enum\HashAlgorithm;

class HashAlgorithmTest extends TestCase
{
    #[Test]
    public function sha256HasCorrectValue(): void
    {
        $this->assertSame('SHA256', HashAlgorithm::SHA256->value);
    }

    #[Test]
    public function sha384HasCorrectValue(): void
    {
        $this->assertSame('SHA384', HashAlgorithm::SHA384->value);
    }

    #[Test]
    public function sha512HasCorrectValue(): void
    {
        $this->assertSame('SHA512', HashAlgorithm::SHA512->value);
    }

    #[Test]
    public function sha256ReturnsCorrectDigestAlgorithm(): void
    {
        $this->assertSame('sha256', HashAlgorithm::SHA256->getDigestAlgorithm());
    }

    #[Test]
    public function sha384ReturnsCorrectDigestAlgorithm(): void
    {
        $this->assertSame('sha384', HashAlgorithm::SHA384->getDigestAlgorithm());
    }

    #[Test]
    public function sha512ReturnsCorrectDigestAlgorithm(): void
    {
        $this->assertSame('sha512', HashAlgorithm::SHA512->getDigestAlgorithm());
    }

    #[Test]
    public function sha256ReturnsCorrectHashLength(): void
    {
        $this->assertSame(32, HashAlgorithm::SHA256->getHashLength());
    }

    #[Test]
    public function sha384ReturnsCorrectHashLength(): void
    {
        $this->assertSame(48, HashAlgorithm::SHA384->getHashLength());
    }

    #[Test]
    public function sha512ReturnsCorrectHashLength(): void
    {
        $this->assertSame(64, HashAlgorithm::SHA512->getHashLength());
    }
}
