<?php

declare(strict_types=1);

namespace Sk\SmartId\Tests\Enum;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\Enum\InteractionType;

class InteractionTypeTest extends TestCase
{
    #[Test]
    public function displayTextAndPinHasCorrectValue(): void
    {
        $this->assertSame('displayTextAndPIN', InteractionType::DISPLAY_TEXT_AND_PIN->value);
    }

    #[Test]
    public function verificationCodeChoiceHasCorrectValue(): void
    {
        $this->assertSame('verificationCodeChoice', InteractionType::VERIFICATION_CODE_CHOICE->value);
    }

    #[Test]
    public function confirmationMessageHasCorrectValue(): void
    {
        $this->assertSame('confirmationMessage', InteractionType::CONFIRMATION_MESSAGE->value);
    }

    #[Test]
    public function confirmationMessageAndVerificationCodeChoiceHasCorrectValue(): void
    {
        $this->assertSame(
            'confirmationMessageAndVerificationCodeChoice',
            InteractionType::CONFIRMATION_MESSAGE_AND_VERIFICATION_CODE_CHOICE->value,
        );
    }
}
