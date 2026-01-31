<?php

declare(strict_types=1);

namespace Sk\SmartId\Tests\Util;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\Util\RpChallengeGenerator;

class RpChallengeGeneratorTest extends TestCase
{
    #[Test]
    public function generateReturnsBase64EncodedString(): void
    {
        $challenge = RpChallengeGenerator::generate();

        $decoded = base64_decode($challenge, true);
        $this->assertNotFalse($decoded);
        $this->assertSame(32, strlen($decoded));
    }

    #[Test]
    public function generateWithCustomLengthReturnsCorrectSize(): void
    {
        $challenge = RpChallengeGenerator::generate(64);

        $decoded = base64_decode($challenge, true);
        $this->assertNotFalse($decoded);
        $this->assertSame(64, strlen($decoded));
    }

    #[Test]
    public function generateReturnsDifferentValuesEachTime(): void
    {
        $challenge1 = RpChallengeGenerator::generate();
        $challenge2 = RpChallengeGenerator::generate();

        $this->assertNotSame($challenge1, $challenge2);
    }
}
