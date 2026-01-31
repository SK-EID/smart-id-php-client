<?php

declare(strict_types=1);

namespace Sk\SmartId\Tests\Enum;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\Enum\SessionType;

class SessionTypeTest extends TestCase
{
    #[Test]
    public function authenticationHasCorrectValue(): void
    {
        $this->assertSame('auth', SessionType::AUTHENTICATION->value);
    }
}
