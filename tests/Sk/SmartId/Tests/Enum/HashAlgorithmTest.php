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

namespace Sk\SmartId\Tests\Enum;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\Enum\HashAlgorithm;

class HashAlgorithmTest extends TestCase
{
    #[Test]
    public function sha256HasCorrectValue(): void
    {
        $this->assertSame('SHA-256', HashAlgorithm::SHA256->value);
    }

    #[Test]
    public function sha384HasCorrectValue(): void
    {
        $this->assertSame('SHA-384', HashAlgorithm::SHA384->value);
    }

    #[Test]
    public function sha512HasCorrectValue(): void
    {
        $this->assertSame('SHA-512', HashAlgorithm::SHA512->value);
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
