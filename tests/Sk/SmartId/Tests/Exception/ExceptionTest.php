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
use Sk\SmartId\Exception\SessionNotFoundException;
use Sk\SmartId\Exception\SessionTimeoutException;
use Sk\SmartId\Exception\SmartIdException;
use Sk\SmartId\Exception\TechnicalErrorException;
use Sk\SmartId\Exception\UserRefusedException;

class ExceptionTest extends TestCase
{
    #[Test]
    public function smartIdExceptionExtendsException(): void
    {
        $exception = new SmartIdException('Test message');

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertSame('Test message', $exception->getMessage());
    }

    #[Test]
    public function sessionNotFoundExceptionExtendsSmartIdException(): void
    {
        $exception = new SessionNotFoundException('Session not found');

        $this->assertInstanceOf(SmartIdException::class, $exception);
        $this->assertSame('Session not found', $exception->getMessage());
    }

    #[Test]
    public function userRefusedExceptionExtendsSmartIdException(): void
    {
        $exception = new UserRefusedException('User refused');

        $this->assertInstanceOf(SmartIdException::class, $exception);
        $this->assertSame('User refused', $exception->getMessage());
    }

    #[Test]
    public function sessionTimeoutExceptionExtendsSmartIdException(): void
    {
        $exception = new SessionTimeoutException('Session timeout');

        $this->assertInstanceOf(SmartIdException::class, $exception);
        $this->assertSame('Session timeout', $exception->getMessage());
    }

    #[Test]
    public function technicalErrorExceptionExtendsSmartIdException(): void
    {
        $exception = new TechnicalErrorException('Technical error');

        $this->assertInstanceOf(SmartIdException::class, $exception);
        $this->assertSame('Technical error', $exception->getMessage());
    }
}
