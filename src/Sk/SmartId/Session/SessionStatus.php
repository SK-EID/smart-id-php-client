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

class SessionStatus
{
    public const STATE_RUNNING = 'RUNNING';

    public const STATE_COMPLETE = 'COMPLETE';

    /**
     * @param string[]|null $ignoredProperties
     */
    public function __construct(
        private readonly string $state,
        private readonly ?SessionResult $result = null,
        private readonly ?SessionCertificate $cert = null,
        private readonly ?SessionSignature $signature = null,
        private readonly ?string $signatureProtocol = null,
        private readonly ?string $deviceIpAddress = null,
        private readonly ?string $interactionTypeUsed = null,
        private readonly ?array $ignoredProperties = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $result = null;
        if (isset($data['result']) && is_array($data['result'])) {
            /** @var array<string, mixed> $resultData */
            $resultData = $data['result'];
            $result = SessionResult::fromArray($resultData);
        }

        $cert = null;
        if (isset($data['cert']) && is_array($data['cert'])) {
            /** @var array<string, string> $certData */
            $certData = $data['cert'];
            $cert = SessionCertificate::fromArray($certData);
        }

        $signature = null;
        if (isset($data['signature']) && is_array($data['signature'])) {
            /** @var array<string, mixed> $signatureData */
            $signatureData = $data['signature'];
            $signature = SessionSignature::fromArray($signatureData);
        }

        $state = $data['state'];
        if (!is_string($state)) {
            throw new \InvalidArgumentException('state must be a string');
        }

        $signatureProtocol = $data['signatureProtocol'] ?? null;
        $deviceIpAddress = $data['deviceIpAddress'] ?? null;
        $interactionTypeUsed = $data['interactionTypeUsed'] ?? null;

        $ignoredProperties = null;
        if (isset($data['ignoredProperties']) && is_array($data['ignoredProperties'])) {
            /** @var string[] $ignoredProperties */
            $ignoredProperties = array_filter($data['ignoredProperties'], 'is_string');
        }

        return new self(
            $state,
            $result,
            $cert,
            $signature,
            is_string($signatureProtocol) ? $signatureProtocol : null,
            is_string($deviceIpAddress) ? $deviceIpAddress : null,
            is_string($interactionTypeUsed) ? $interactionTypeUsed : null,
            $ignoredProperties,
        );
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getResult(): ?SessionResult
    {
        return $this->result;
    }

    public function isRunning(): bool
    {
        return $this->state === self::STATE_RUNNING;
    }

    public function isComplete(): bool
    {
        return $this->state === self::STATE_COMPLETE;
    }

    public function getCert(): ?SessionCertificate
    {
        return $this->cert;
    }

    public function getSignature(): ?SessionSignature
    {
        return $this->signature;
    }

    public function getSignatureProtocol(): ?string
    {
        return $this->signatureProtocol;
    }

    public function getDeviceIpAddress(): ?string
    {
        return $this->deviceIpAddress;
    }

    public function getInteractionTypeUsed(): ?string
    {
        return $this->interactionTypeUsed;
    }

    /**
     * @return string[]|null
     */
    public function getIgnoredProperties(): ?array
    {
        return $this->ignoredProperties;
    }
}
