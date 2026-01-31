<?php

declare(strict_types=1);

namespace Sk\SmartId\Enum;

enum InteractionType: string
{
    case DISPLAY_TEXT_AND_PIN = 'displayTextAndPIN';
    case VERIFICATION_CODE_CHOICE = 'verificationCodeChoice';
    case CONFIRMATION_MESSAGE = 'confirmationMessage';
    case CONFIRMATION_MESSAGE_AND_VERIFICATION_CODE_CHOICE = 'confirmationMessageAndVerificationCodeChoice';
}
