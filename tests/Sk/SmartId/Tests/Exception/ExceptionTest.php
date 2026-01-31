<?php

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
