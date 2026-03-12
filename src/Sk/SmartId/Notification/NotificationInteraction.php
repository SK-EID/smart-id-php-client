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

namespace Sk\SmartId\Notification;

use Sk\SmartId\Enum\InteractionType;
use Sk\SmartId\Model\AbstractInteraction;

/**
 * Interaction types available for notification-based flows.
 *
 * Notification-based flows support three interaction types:
 * - displayTextAndPIN: short text (up to 60 characters) with PIN input
 * - confirmationMessage: longer text (up to 200 characters) with Confirm/Cancel buttons
 * - confirmationMessageAndVerificationCodeChoice: longer text (up to 200 characters) with verification code selection
 *
 * For device link flows, use DeviceLinkInteraction which supports only
 * displayTextAndPIN and confirmationMessage.
 */
class NotificationInteraction extends AbstractInteraction
{
    public static function displayTextAndPin(string $displayText): self
    {
        return new self(InteractionType::DISPLAY_TEXT_AND_PIN, $displayText);
    }

    public static function confirmationMessage(string $displayText): self
    {
        return new self(InteractionType::CONFIRMATION_MESSAGE, $displayText);
    }

    public static function confirmationMessageAndVerificationCodeChoice(string $displayText): self
    {
        return new self(InteractionType::CONFIRMATION_MESSAGE_AND_VERIFICATION_CODE_CHOICE, $displayText);
    }
}
