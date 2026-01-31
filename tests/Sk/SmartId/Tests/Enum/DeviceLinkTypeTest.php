<?php

declare(strict_types=1);

namespace Sk\SmartId\Tests\Enum;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\Enum\DeviceLinkType;

class DeviceLinkTypeTest extends TestCase
{
    #[Test]
    public function qrHasCorrectValue(): void
    {
        $this->assertSame('qr', DeviceLinkType::QR->value);
    }

    #[Test]
    public function web2appHasCorrectValue(): void
    {
        $this->assertSame('web2app', DeviceLinkType::WEB2APP->value);
    }

    #[Test]
    public function app2appHasCorrectValue(): void
    {
        $this->assertSame('app2app', DeviceLinkType::APP2APP->value);
    }
}
