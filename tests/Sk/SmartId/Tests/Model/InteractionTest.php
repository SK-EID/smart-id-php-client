<?php

declare(strict_types=1);

namespace Sk\SmartId\Tests\Model;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\Enum\InteractionType;
use Sk\SmartId\Model\Interaction;

class InteractionTest extends TestCase
{
    #[Test]
    public function displayTextAndPinCreatesCorrectInteraction(): void
    {
        $interaction = Interaction::displayTextAndPin('Please confirm');

        $this->assertSame(InteractionType::DISPLAY_TEXT_AND_PIN, $interaction->getType());
        $this->assertSame('Please confirm', $interaction->getDisplayText());
    }

    #[Test]
    public function verificationCodeChoiceCreatesCorrectInteraction(): void
    {
        $interaction = Interaction::verificationCodeChoice();

        $this->assertSame(InteractionType::VERIFICATION_CODE_CHOICE, $interaction->getType());
        $this->assertNull($interaction->getDisplayText());
    }

    #[Test]
    public function confirmationMessageCreatesCorrectInteraction(): void
    {
        $interaction = Interaction::confirmationMessage('Confirm payment');

        $this->assertSame(InteractionType::CONFIRMATION_MESSAGE, $interaction->getType());
        $this->assertSame('Confirm payment', $interaction->getDisplayText());
    }

    #[Test]
    public function confirmationMessageAndVerificationCodeChoiceCreatesCorrectInteraction(): void
    {
        $interaction = Interaction::confirmationMessageAndVerificationCodeChoice('Confirm and verify');

        $this->assertSame(
            InteractionType::CONFIRMATION_MESSAGE_AND_VERIFICATION_CODE_CHOICE,
            $interaction->getType(),
        );
        $this->assertSame('Confirm and verify', $interaction->getDisplayText());
    }

    #[Test]
    public function toArrayReturnsCorrectStructureWithDisplayText60(): void
    {
        $interaction = Interaction::displayTextAndPin('Test message');
        $array = $interaction->toArray();

        $this->assertSame([
            'type' => 'displayTextAndPIN',
            'displayText60' => 'Test message',
        ], $array);
    }

    #[Test]
    public function toArrayReturnsCorrectStructureWithDisplayText200(): void
    {
        $interaction = Interaction::confirmationMessage('Longer confirmation message');
        $array = $interaction->toArray();

        $this->assertSame([
            'type' => 'confirmationMessage',
            'displayText200' => 'Longer confirmation message',
        ], $array);
    }

    #[Test]
    public function toArrayReturnsCorrectStructureWithoutText(): void
    {
        $interaction = Interaction::verificationCodeChoice();
        $array = $interaction->toArray();

        $this->assertSame([
            'type' => 'verificationCodeChoice',
        ], $array);
    }

    #[Test]
    public function toPayloadStringWithText(): void
    {
        $interaction = Interaction::displayTextAndPin('Test');
        $payload = $interaction->toPayloadString();

        $this->assertSame('displayTextAndPIN:' . base64_encode('Test'), $payload);
    }

    #[Test]
    public function toPayloadStringWithoutText(): void
    {
        $interaction = Interaction::verificationCodeChoice();
        $payload = $interaction->toPayloadString();

        $this->assertSame('verificationCodeChoice:', $payload);
    }

    #[Test]
    public function constructorWithCustomParameters(): void
    {
        $interaction = new Interaction(InteractionType::DISPLAY_TEXT_AND_PIN, 'Custom text');

        $this->assertSame(InteractionType::DISPLAY_TEXT_AND_PIN, $interaction->getType());
        $this->assertSame('Custom text', $interaction->getDisplayText());
    }

    #[Test]
    public function constructorWithNullDisplayText(): void
    {
        $interaction = new Interaction(InteractionType::VERIFICATION_CODE_CHOICE);

        $this->assertNull($interaction->getDisplayText());
    }

    #[Test]
    public function maxDisplayTextConstantsHaveCorrectValues(): void
    {
        $this->assertSame(60, Interaction::MAX_DISPLAY_TEXT_60);
        $this->assertSame(200, Interaction::MAX_DISPLAY_TEXT_200);
    }

    #[Test]
    public function getMaxDisplayTextLengthReturnsCorrectValueForEachType(): void
    {
        $this->assertSame(60, Interaction::displayTextAndPin('test')->getMaxDisplayTextLength());
        $this->assertSame(0, Interaction::verificationCodeChoice()->getMaxDisplayTextLength());
        $this->assertSame(200, Interaction::confirmationMessage('test')->getMaxDisplayTextLength());
        $this->assertSame(200, Interaction::confirmationMessageAndVerificationCodeChoice('test')->getMaxDisplayTextLength());
    }

    #[Test]
    public function displayTextAndPinAcceptsTextAt60Characters(): void
    {
        $text = str_repeat('a', 60);
        $interaction = Interaction::displayTextAndPin($text);

        $this->assertSame($text, $interaction->getDisplayText());
    }

    #[Test]
    public function displayTextAndPinThrowsWhenTextExceeds60Characters(): void
    {
        $text = str_repeat('a', 61);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Display text for displayTextAndPIN must not exceed 60 characters, 61 given');

        Interaction::displayTextAndPin($text);
    }

    #[Test]
    public function confirmationMessageAcceptsTextAt200Characters(): void
    {
        $text = str_repeat('b', 200);
        $interaction = Interaction::confirmationMessage($text);

        $this->assertSame($text, $interaction->getDisplayText());
    }

    #[Test]
    public function confirmationMessageThrowsWhenTextExceeds200Characters(): void
    {
        $text = str_repeat('c', 201);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Display text for confirmationMessage must not exceed 200 characters, 201 given');

        Interaction::confirmationMessage($text);
    }

    #[Test]
    public function confirmationMessageAndVerificationCodeChoiceAcceptsTextAt200Characters(): void
    {
        $text = str_repeat('d', 200);
        $interaction = Interaction::confirmationMessageAndVerificationCodeChoice($text);

        $this->assertSame($text, $interaction->getDisplayText());
    }

    #[Test]
    public function confirmationMessageAndVerificationCodeChoiceThrowsWhenTextExceeds200Characters(): void
    {
        $text = str_repeat('e', 201);

        $this->expectException(\InvalidArgumentException::class);

        Interaction::confirmationMessageAndVerificationCodeChoice($text);
    }

    #[Test]
    public function displayTextAndPinHandlesMultibyteCharactersCorrectly(): void
    {
        $text = str_repeat('ä', 60);
        $interaction = Interaction::displayTextAndPin($text);

        $this->assertSame($text, $interaction->getDisplayText());
    }

    #[Test]
    public function displayTextAndPinThrowsForMultibyteTextExceedingMaxLength(): void
    {
        $text = str_repeat('ä', 61);

        $this->expectException(\InvalidArgumentException::class);

        Interaction::displayTextAndPin($text);
    }
}
