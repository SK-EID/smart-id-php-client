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
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\Util\VerificationCodeCalculator;

class VerificationCodeCalculatorTest extends TestCase
{
    #[Test]
    public function calculateReturns4DigitCode(): void
    {
        $hash = hash('sha512', 'test', true);
        $code = VerificationCodeCalculator::calculate($hash);

        $this->assertSame(4, strlen($code));
        $this->assertMatchesRegularExpression('/^\d{4}$/', $code);
    }

    #[Test]
    public function calculateHandlesHexEncodedSha256Hash(): void
    {
        $hash = hash('sha256', 'test');
        $code = VerificationCodeCalculator::calculate($hash);

        $this->assertSame(4, strlen($code));
    }

    #[Test]
    public function calculateHandlesHexEncodedSha384Hash(): void
    {
        $hash = hash('sha384', 'test');
        $code = VerificationCodeCalculator::calculate($hash);

        $this->assertSame(4, strlen($code));
    }

    #[Test]
    public function calculateHandlesHexEncodedSha512Hash(): void
    {
        $hash = hash('sha512', 'test');
        $code = VerificationCodeCalculator::calculate($hash);

        $this->assertSame(4, strlen($code));
    }

    #[Test]
    public function calculateFromRpChallengeReturns4DigitCode(): void
    {
        $rpChallenge = base64_encode(random_bytes(32));
        $code = VerificationCodeCalculator::calculateFromRpChallenge($rpChallenge);

        $this->assertSame(4, strlen($code));
        $this->assertMatchesRegularExpression('/^\d{4}$/', $code);
    }

    #[Test]
    public function calculateFromRpChallengeThrowsOnInvalidBase64(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid base64 encoded rpChallenge');

        VerificationCodeCalculator::calculateFromRpChallenge('not-valid-base64!!!');
    }

    #[Test]
    public function calculatePadsWithLeadingZeros(): void
    {
        // Create a hash that results in a small number (< 1000)
        // We'll iterate until we find one that produces leading zeros
        $found = false;
        for ($i = 0; $i < 1000; $i++) {
            $hash = hash('sha512', 'test' . $i, true);
            $integralBytes = substr($hash, -2);
            $integralValue = unpack('n', $integralBytes)[1];
            $value = $integralValue % 10000;

            if ($value < 1000) {
                $code = VerificationCodeCalculator::calculate($hash);
                $this->assertSame(4, strlen($code));
                $this->assertSame(str_pad((string) $value, 4, '0', STR_PAD_LEFT), $code);
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, 'Could not find a hash that produces a code with leading zeros');
    }
}
