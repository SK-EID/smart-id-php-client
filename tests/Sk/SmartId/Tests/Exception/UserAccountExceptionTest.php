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

namespace Sk\SmartId\Tests\Exception;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\Exception\SmartIdException;
use Sk\SmartId\Exception\UserAccountException;

class UserAccountExceptionTest extends TestCase
{
    #[Test]
    public function extendsSmartIdException(): void
    {
        $exception = new UserAccountException('Test', 471);

        $this->assertInstanceOf(SmartIdException::class, $exception);
    }

    #[Test]
    public function getErrorCodeReturnsCode(): void
    {
        $exception = new UserAccountException('Test', 471);

        $this->assertSame(471, $exception->getErrorCode());
    }

    #[Test]
    public function isNoSuitableAccountReturnsTrueFor471(): void
    {
        $exception = new UserAccountException('Test', UserAccountException::NO_SUITABLE_ACCOUNT);

        $this->assertTrue($exception->isNoSuitableAccount());
        $this->assertFalse($exception->isPersonShouldViewApp());
        $this->assertFalse($exception->isClientTooOld());
    }

    #[Test]
    public function isPersonShouldViewAppReturnsTrueFor472(): void
    {
        $exception = new UserAccountException('Test', UserAccountException::PERSON_SHOULD_VIEW_APP);

        $this->assertFalse($exception->isNoSuitableAccount());
        $this->assertTrue($exception->isPersonShouldViewApp());
        $this->assertFalse($exception->isClientTooOld());
    }

    #[Test]
    public function isClientTooOldReturnsTrueFor480(): void
    {
        $exception = new UserAccountException('Test', UserAccountException::CLIENT_TOO_OLD);

        $this->assertFalse($exception->isNoSuitableAccount());
        $this->assertFalse($exception->isPersonShouldViewApp());
        $this->assertTrue($exception->isClientTooOld());
    }

    #[Test]
    public function constantsHaveCorrectValues(): void
    {
        $this->assertSame(471, UserAccountException::NO_SUITABLE_ACCOUNT);
        $this->assertSame(472, UserAccountException::PERSON_SHOULD_VIEW_APP);
        $this->assertSame(480, UserAccountException::CLIENT_TOO_OLD);
    }
}
