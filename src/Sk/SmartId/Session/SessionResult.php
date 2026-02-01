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

namespace Sk\SmartId\Session;

class SessionResult
{
    public const END_RESULT_OK = 'OK';

    public const END_RESULT_USER_REFUSED = 'USER_REFUSED';

    public const END_RESULT_TIMEOUT = 'TIMEOUT';

    public const END_RESULT_DOCUMENT_UNUSABLE = 'DOCUMENT_UNUSABLE';

    public const END_RESULT_WRONG_VC = 'WRONG_VC';

    public const END_RESULT_REQUIRED_INTERACTION_NOT_SUPPORTED_BY_APP = 'REQUIRED_INTERACTION_NOT_SUPPORTED_BY_APP';

    public const END_RESULT_USER_REFUSED_CERT_CHOICE = 'USER_REFUSED_CERT_CHOICE';

    public const END_RESULT_USER_REFUSED_DISPLAYTEXTANDPIN = 'USER_REFUSED_DISPLAYTEXTANDPIN';

    public const END_RESULT_USER_REFUSED_VC_CHOICE = 'USER_REFUSED_VC_CHOICE';

    public const END_RESULT_USER_REFUSED_CONFIRMATIONMESSAGE = 'USER_REFUSED_CONFIRMATIONMESSAGE';

    public const END_RESULT_USER_REFUSED_CONFIRMATIONMESSAGE_WITH_VC_CHOICE = 'USER_REFUSED_CONFIRMATIONMESSAGE_WITH_VC_CHOICE';

    public function __construct(
        private readonly string $endResult,
        private readonly ?string $documentNumber = null,
        private readonly ?string $interactionFlowUsed = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $endResult = $data['endResult'];
        if (!is_string($endResult)) {
            throw new \InvalidArgumentException('endResult must be a string');
        }

        $documentNumber = $data['documentNumber'] ?? null;
        $interactionFlowUsed = $data['interactionFlowUsed'] ?? null;

        return new self(
            $endResult,
            is_string($documentNumber) ? $documentNumber : null,
            is_string($interactionFlowUsed) ? $interactionFlowUsed : null,
        );
    }

    public function getEndResult(): string
    {
        return $this->endResult;
    }

    public function getDocumentNumber(): ?string
    {
        return $this->documentNumber;
    }

    public function getInteractionFlowUsed(): ?string
    {
        return $this->interactionFlowUsed;
    }

    public function isOk(): bool
    {
        return $this->endResult === self::END_RESULT_OK;
    }

    public function isUserRefused(): bool
    {
        return str_starts_with($this->endResult, self::END_RESULT_USER_REFUSED);
    }

    public function isTimeout(): bool
    {
        return $this->endResult === self::END_RESULT_TIMEOUT;
    }

    public function isDocumentUnusable(): bool
    {
        return $this->endResult === self::END_RESULT_DOCUMENT_UNUSABLE;
    }

    public function isWrongVC(): bool
    {
        return $this->endResult === self::END_RESULT_WRONG_VC;
    }

    public function isRequiredInteractionNotSupported(): bool
    {
        return $this->endResult === self::END_RESULT_REQUIRED_INTERACTION_NOT_SUPPORTED_BY_APP;
    }
}
