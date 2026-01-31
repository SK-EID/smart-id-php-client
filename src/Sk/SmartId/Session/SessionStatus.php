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

    public function __construct(
        private readonly string $state,
        private readonly ?SessionResult $result = null,
        private readonly ?SessionCertificate $cert = null,
        private readonly ?SessionSignature $signature = null,
        private readonly ?string $deviceIpAddress = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $result = null;
        if (isset($data['result'])) {
            $result = SessionResult::fromArray($data['result']);
        }

        $cert = null;
        if (isset($data['cert'])) {
            $cert = SessionCertificate::fromArray($data['cert']);
        }

        $signature = null;
        if (isset($data['signature'])) {
            $signature = SessionSignature::fromArray($data['signature']);
        }

        return new self(
            $data['state'],
            $result,
            $cert,
            $signature,
            $data['deviceIpAddress'] ?? null,
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

    public function getDeviceIpAddress(): ?string
    {
        return $this->deviceIpAddress;
    }
}
