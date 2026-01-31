<?php

declare(strict_types=1);

namespace Sk\SmartId\Tests\Enum;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\Enum\CertificateLevel;

class CertificateLevelTest extends TestCase
{
    #[Test]
    public function qualifiedHasCorrectValue(): void
    {
        $this->assertSame('QUALIFIED', CertificateLevel::QUALIFIED->value);
    }

    #[Test]
    public function advancedHasCorrectValue(): void
    {
        $this->assertSame('ADVANCED', CertificateLevel::ADVANCED->value);
    }
}
