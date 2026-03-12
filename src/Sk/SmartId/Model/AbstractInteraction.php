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

namespace Sk\SmartId\Model;

use Sk\SmartId\Enum\InteractionType;

abstract class AbstractInteraction
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
            InteractionType::CONFIRMATION_MESSAGE,
            InteractionType::CONFIRMATION_MESSAGE_AND_VERIFICATION_CODE_CHOICE => self::MAX_DISPLAY_TEXT_200,
        };
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
