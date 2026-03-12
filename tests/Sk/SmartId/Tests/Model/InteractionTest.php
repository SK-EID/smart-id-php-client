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

namespace Sk\SmartId\Tests\Model;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\DeviceLink\DeviceLinkInteraction;
use Sk\SmartId\Enum\InteractionType;
use Sk\SmartId\Model\AbstractInteraction;
use Sk\SmartId\Notification\NotificationInteraction;

class InteractionTest extends TestCase
{
    // =========================================================================
    // DeviceLinkInteraction tests
    // =========================================================================

    #[Test]
    public function deviceLinkDisplayTextAndPinCreatesCorrectInteraction(): void
    {
        $interaction = DeviceLinkInteraction::displayTextAndPin('Please confirm');

        $this->assertSame(InteractionType::DISPLAY_TEXT_AND_PIN, $interaction->getType());
        $this->assertSame('Please confirm', $interaction->getDisplayText());
    }

    #[Test]
    public function deviceLinkConfirmationMessageCreatesCorrectInteraction(): void
    {
        $interaction = DeviceLinkInteraction::confirmationMessage('Confirm payment');

        $this->assertSame(InteractionType::CONFIRMATION_MESSAGE, $interaction->getType());
        $this->assertSame('Confirm payment', $interaction->getDisplayText());
    }

    #[Test]
    public function deviceLinkInteractionExtendsAbstractInteraction(): void
    {
        $interaction = DeviceLinkInteraction::displayTextAndPin('test');

        $this->assertInstanceOf(AbstractInteraction::class, $interaction);
    }

    // =========================================================================
    // NotificationInteraction tests
    // =========================================================================

    #[Test]
    public function notificationDisplayTextAndPinCreatesCorrectInteraction(): void
    {
        $interaction = NotificationInteraction::displayTextAndPin('Please confirm');

        $this->assertSame(InteractionType::DISPLAY_TEXT_AND_PIN, $interaction->getType());
        $this->assertSame('Please confirm', $interaction->getDisplayText());
    }

    #[Test]
    public function notificationConfirmationMessageCreatesCorrectInteraction(): void
    {
        $interaction = NotificationInteraction::confirmationMessage('Confirm payment');

        $this->assertSame(InteractionType::CONFIRMATION_MESSAGE, $interaction->getType());
        $this->assertSame('Confirm payment', $interaction->getDisplayText());
    }

    #[Test]
    public function notificationConfirmationMessageAndVerificationCodeChoiceCreatesCorrectInteraction(): void
    {
        $interaction = NotificationInteraction::confirmationMessageAndVerificationCodeChoice('Confirm and verify');

        $this->assertSame(
            InteractionType::CONFIRMATION_MESSAGE_AND_VERIFICATION_CODE_CHOICE,
            $interaction->getType(),
        );
        $this->assertSame('Confirm and verify', $interaction->getDisplayText());
    }

    #[Test]
    public function notificationInteractionExtendsAbstractInteraction(): void
    {
        $interaction = NotificationInteraction::displayTextAndPin('test');

        $this->assertInstanceOf(AbstractInteraction::class, $interaction);
    }

    // =========================================================================
    // toArray tests
    // =========================================================================

    #[Test]
    public function toArrayReturnsCorrectStructureWithDisplayText60(): void
    {
        $interaction = DeviceLinkInteraction::displayTextAndPin('Test message');
        $array = $interaction->toArray();

        $this->assertSame([
            'type' => 'displayTextAndPIN',
            'displayText60' => 'Test message',
        ], $array);
    }

    #[Test]
    public function toArrayReturnsCorrectStructureWithDisplayText200(): void
    {
        $interaction = DeviceLinkInteraction::confirmationMessage('Longer confirmation message');
        $array = $interaction->toArray();

        $this->assertSame([
            'type' => 'confirmationMessage',
            'displayText200' => 'Longer confirmation message',
        ], $array);
    }

    #[Test]
    public function toArrayReturnsCorrectStructureForConfirmationMessageAndVerificationCodeChoice(): void
    {
        $interaction = NotificationInteraction::confirmationMessageAndVerificationCodeChoice('Verify this');
        $array = $interaction->toArray();

        $this->assertSame([
            'type' => 'confirmationMessageAndVerificationCodeChoice',
            'displayText200' => 'Verify this',
        ], $array);
    }

    // =========================================================================
    // toPayloadString tests
    // =========================================================================

    #[Test]
    public function toPayloadStringWithText(): void
    {
        $interaction = DeviceLinkInteraction::displayTextAndPin('Test');
        $payload = $interaction->toPayloadString();

        $this->assertSame('displayTextAndPIN:' . base64_encode('Test'), $payload);
    }

    #[Test]
    public function toPayloadStringForConfirmationMessage(): void
    {
        $interaction = NotificationInteraction::confirmationMessage('Confirm');
        $payload = $interaction->toPayloadString();

        $this->assertSame('confirmationMessage:' . base64_encode('Confirm'), $payload);
    }

    // =========================================================================
    // Constants and max length tests
    // =========================================================================

    #[Test]
    public function maxDisplayTextConstantsHaveCorrectValues(): void
    {
        $this->assertSame(60, AbstractInteraction::MAX_DISPLAY_TEXT_60);
        $this->assertSame(200, AbstractInteraction::MAX_DISPLAY_TEXT_200);
    }

    #[Test]
    public function getMaxDisplayTextLengthReturnsCorrectValueForEachType(): void
    {
        $this->assertSame(60, DeviceLinkInteraction::displayTextAndPin('test')->getMaxDisplayTextLength());
        $this->assertSame(200, DeviceLinkInteraction::confirmationMessage('test')->getMaxDisplayTextLength());
        $this->assertSame(200, NotificationInteraction::confirmationMessageAndVerificationCodeChoice('test')->getMaxDisplayTextLength());
    }

    // =========================================================================
    // Boundary and validation tests
    // =========================================================================

    #[Test]
    public function displayTextAndPinAcceptsTextAt60Characters(): void
    {
        $text = str_repeat('a', 60);
        $interaction = DeviceLinkInteraction::displayTextAndPin($text);

        $this->assertSame($text, $interaction->getDisplayText());
    }

    #[Test]
    public function displayTextAndPinThrowsWhenTextExceeds60Characters(): void
    {
        $text = str_repeat('a', 61);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Display text for displayTextAndPIN must not exceed 60 characters, 61 given');

        DeviceLinkInteraction::displayTextAndPin($text);
    }

    #[Test]
    public function confirmationMessageAcceptsTextAt200Characters(): void
    {
        $text = str_repeat('b', 200);
        $interaction = DeviceLinkInteraction::confirmationMessage($text);

        $this->assertSame($text, $interaction->getDisplayText());
    }

    #[Test]
    public function confirmationMessageThrowsWhenTextExceeds200Characters(): void
    {
        $text = str_repeat('c', 201);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Display text for confirmationMessage must not exceed 200 characters, 201 given');

        DeviceLinkInteraction::confirmationMessage($text);
    }

    #[Test]
    public function confirmationMessageAndVerificationCodeChoiceAcceptsTextAt200Characters(): void
    {
        $text = str_repeat('d', 200);
        $interaction = NotificationInteraction::confirmationMessageAndVerificationCodeChoice($text);

        $this->assertSame($text, $interaction->getDisplayText());
    }

    #[Test]
    public function confirmationMessageAndVerificationCodeChoiceThrowsWhenTextExceeds200Characters(): void
    {
        $text = str_repeat('e', 201);

        $this->expectException(\InvalidArgumentException::class);

        NotificationInteraction::confirmationMessageAndVerificationCodeChoice($text);
    }

    #[Test]
    public function displayTextAndPinHandlesMultibyteCharactersCorrectly(): void
    {
        $text = str_repeat('ä', 60);
        $interaction = DeviceLinkInteraction::displayTextAndPin($text);

        $this->assertSame($text, $interaction->getDisplayText());
    }

    #[Test]
    public function displayTextAndPinThrowsForMultibyteTextExceedingMaxLength(): void
    {
        $text = str_repeat('ä', 61);

        $this->expectException(\InvalidArgumentException::class);

        DeviceLinkInteraction::displayTextAndPin($text);
    }
}
