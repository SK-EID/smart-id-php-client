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
use Sk\SmartId\Enum\FlowType;

class FlowTypeTest extends TestCase
{
    #[Test]
    public function fromStringReturnsCorrectCases(): void
    {
        $this->assertSame(FlowType::QR, FlowType::fromString('qr'));
        $this->assertSame(FlowType::WEB2APP, FlowType::fromString('web2app'));
        $this->assertSame(FlowType::APP2APP, FlowType::fromString('app2app'));
        $this->assertSame(FlowType::NOTIFICATION, FlowType::fromString('notification'));
    }

    #[Test]
    public function fromStringIsCaseInsensitive(): void
    {
        $this->assertSame(FlowType::QR, FlowType::fromString('QR'));
        $this->assertSame(FlowType::WEB2APP, FlowType::fromString('Web2App'));
    }

    #[Test]
    public function fromStringReturnsNullForUnknown(): void
    {
        $this->assertNull(FlowType::fromString('unknown'));
    }

    #[Test]
    public function isSupportedReturnsTrueForValid(): void
    {
        $this->assertTrue(FlowType::isSupported('qr'));
        $this->assertTrue(FlowType::isSupported('notification'));
    }

    #[Test]
    public function isSupportedReturnsFalseForInvalid(): void
    {
        $this->assertFalse(FlowType::isSupported('unsupported'));
    }
}
