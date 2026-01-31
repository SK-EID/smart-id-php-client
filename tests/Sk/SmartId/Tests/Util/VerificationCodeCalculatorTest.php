<?php

declare(strict_types=1);

namespace Sk\SmartId\Tests\Util;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\Enum\HashAlgorithm;
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
    public function calculateFromRpChallengeWithSha512(): void
    {
        $rpChallenge = base64_encode(random_bytes(32));
        $code = VerificationCodeCalculator::calculateFromRpChallenge($rpChallenge, HashAlgorithm::SHA512);

        $this->assertSame(4, strlen($code));
        $this->assertMatchesRegularExpression('/^\d{4}$/', $code);
    }

    #[Test]
    public function calculateFromRpChallengeWithSha256(): void
    {
        $rpChallenge = base64_encode(random_bytes(32));
        $code = VerificationCodeCalculator::calculateFromRpChallenge($rpChallenge, HashAlgorithm::SHA256);

        $this->assertSame(4, strlen($code));
    }

    #[Test]
    public function calculateFromRpChallengeThrowsOnInvalidBase64(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid base64 encoded rpChallenge');

        VerificationCodeCalculator::calculateFromRpChallenge('not-valid-base64!!!', HashAlgorithm::SHA512);
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
