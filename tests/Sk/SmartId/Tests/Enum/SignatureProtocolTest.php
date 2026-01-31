<?php

declare(strict_types=1);

namespace Sk\SmartId\Tests\Enum;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\Enum\SignatureProtocol;

class SignatureProtocolTest extends TestCase
{
    #[Test]
    public function acspV2HasCorrectValue(): void
    {
        $this->assertSame('ACSP_V2', SignatureProtocol::ACSP_V2->value);
    }
}
