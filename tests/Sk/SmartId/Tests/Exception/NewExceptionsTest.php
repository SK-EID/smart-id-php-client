<?php

declare(strict_types=1);

namespace Sk\SmartId\Tests\Exception;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\Exception\DocumentUnusableException;
use Sk\SmartId\Exception\RequiredInteractionNotSupportedException;
use Sk\SmartId\Exception\SmartIdException;
use Sk\SmartId\Exception\ValidationException;
use Sk\SmartId\Exception\WrongVerificationCodeException;

class NewExceptionsTest extends TestCase
{
    #[Test]
    public function documentUnusableExceptionExtendsSmartIdException(): void
    {
        $exception = new DocumentUnusableException('test');

        $this->assertInstanceOf(SmartIdException::class, $exception);
    }

    #[Test]
    public function requiredInteractionNotSupportedExceptionExtendsSmartIdException(): void
    {
        $exception = new RequiredInteractionNotSupportedException('test');

        $this->assertInstanceOf(SmartIdException::class, $exception);
    }

    #[Test]
    public function wrongVerificationCodeExceptionExtendsSmartIdException(): void
    {
        $exception = new WrongVerificationCodeException('test');

        $this->assertInstanceOf(SmartIdException::class, $exception);
    }

    #[Test]
    public function validationExceptionExtendsSmartIdException(): void
    {
        $exception = new ValidationException('test');

        $this->assertInstanceOf(SmartIdException::class, $exception);
    }
}
