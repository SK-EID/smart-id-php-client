<?php

declare(strict_types=1);

namespace Sk\SmartId\Model;

use Sk\SmartId\Enum\InteractionType;

class Interaction
{
    public const MAX_DISPLAY_TEXT_60 = 60;

    public const MAX_DISPLAY_TEXT_200 = 200;

    public function __construct(
        private readonly InteractionType $type,
        private readonly ?string $displayText = null,
    ) {
        if ($displayText !== null) {
            $maxLength = $this->getMaxDisplayTextLength();
            if (mb_strlen($displayText) > $maxLength) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Display text for %s must not exceed %d characters, %d given',
                        $type->value,
                        $maxLength,
                        mb_strlen($displayText),
                    ),
                );
            }
        }
    }

    public function getMaxDisplayTextLength(): int
    {
        return match ($this->type) {
            InteractionType::DISPLAY_TEXT_AND_PIN => self::MAX_DISPLAY_TEXT_60,
            InteractionType::VERIFICATION_CODE_CHOICE => 0,
            InteractionType::CONFIRMATION_MESSAGE,
            InteractionType::CONFIRMATION_MESSAGE_AND_VERIFICATION_CODE_CHOICE => self::MAX_DISPLAY_TEXT_200,
        };
    }

    public static function displayTextAndPin(string $displayText): self
    {
        return new self(InteractionType::DISPLAY_TEXT_AND_PIN, $displayText);
    }

    public static function verificationCodeChoice(): self
    {
        return new self(InteractionType::VERIFICATION_CODE_CHOICE);
    }

    public static function confirmationMessage(string $displayText): self
    {
        return new self(InteractionType::CONFIRMATION_MESSAGE, $displayText);
    }

    public static function confirmationMessageAndVerificationCodeChoice(string $displayText): self
    {
        return new self(InteractionType::CONFIRMATION_MESSAGE_AND_VERIFICATION_CODE_CHOICE, $displayText);
    }

    public function getType(): InteractionType
    {
        return $this->type;
    }

    public function getDisplayText(): ?string
    {
        return $this->displayText;
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        $data = ['type' => $this->type->value];

        if ($this->displayText !== null) {
            $fieldName = $this->getMaxDisplayTextLength() === self::MAX_DISPLAY_TEXT_60
                ? 'displayText60'
                : 'displayText200';
            $data[$fieldName] = $this->displayText;
        }

        return $data;
    }

    public function toPayloadString(): string
    {
        $textBase64 = $this->displayText !== null ? base64_encode($this->displayText) : '';

        return $this->type->value . ':' . $textBase64;
    }
}
