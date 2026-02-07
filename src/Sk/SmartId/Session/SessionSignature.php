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

use Sk\SmartId\Exception\ValidationException;

class SessionSignature
{
    /**
     * @param array<string, string>|null $signatureAlgorithmParameters
     */
    public function __construct(
        private readonly string $value,
        private readonly string $signatureAlgorithm,
        private readonly ?string $serverRandom = null,
        private readonly ?string $userChallenge = null,
        private readonly ?string $flowType = null,
        private readonly ?string $interactionTypeUsed = null,
        private readonly ?array $signatureAlgorithmParameters = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $value = $data['value'];
        $signatureAlgorithm = $data['signatureAlgorithm'];

        if (!is_string($value) || !is_string($signatureAlgorithm)) {
            throw new \InvalidArgumentException('value and signatureAlgorithm must be strings');
        }

        $serverRandom = $data['serverRandom'] ?? null;
        $userChallenge = $data['userChallenge'] ?? null;
        $flowType = $data['flowType'] ?? null;
        $interactionTypeUsed = $data['interactionTypeUsed'] ?? null;

        /** @var array<string, string>|null $algorithmParams */
        $algorithmParams = $data['signatureAlgorithmParameters'] ?? null;

        return new self(
            $value,
            $signatureAlgorithm,
            is_string($serverRandom) ? $serverRandom : null,
            is_string($userChallenge) ? $userChallenge : null,
            is_string($flowType) ? $flowType : null,
            is_string($interactionTypeUsed) ? $interactionTypeUsed : null,
            $algorithmParams,
        );
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getSignatureAlgorithm(): string
    {
        return $this->signatureAlgorithm;
    }

    public function getServerRandom(): ?string
    {
        return $this->serverRandom;
    }

    public function getUserChallenge(): ?string
    {
        return $this->userChallenge;
    }

    public function getFlowType(): ?string
    {
        return $this->flowType;
    }

    public function getInteractionTypeUsed(): ?string
    {
        return $this->interactionTypeUsed;
    }

    /**
     * @return array<string, string>|null
     */
    public function getSignatureAlgorithmParameters(): ?array
    {
        return $this->signatureAlgorithmParameters;
    }

    public function getDecodedValue(): string
    {
        $decoded = base64_decode($this->value, true);
        if ($decoded === false) {
            throw new ValidationException('Invalid base64 encoded signature value');
        }

        return $decoded;
    }
}
