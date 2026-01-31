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

use Sk\SmartId\Api\SmartIdConnector;
use Sk\SmartId\Exception\DocumentUnusableException;
use Sk\SmartId\Exception\RequiredInteractionNotSupportedException;
use Sk\SmartId\Exception\SessionTimeoutException;
use Sk\SmartId\Exception\SmartIdException;
use Sk\SmartId\Exception\UserRefusedException;
use Sk\SmartId\Exception\WrongVerificationCodeException;

class SessionStatusPoller
{
    private const DEFAULT_POLL_TIMEOUT_MS = 30000;

    private const DEFAULT_POLL_INTERVAL_MS = 1000;

    public function __construct(
        private readonly SmartIdConnector $connector,
        private int $pollTimeoutMs = self::DEFAULT_POLL_TIMEOUT_MS,
        private int $pollIntervalMs = self::DEFAULT_POLL_INTERVAL_MS,
    ) {
    }

    public function setPollTimeoutMs(int $pollTimeoutMs): self
    {
        $this->pollTimeoutMs = $pollTimeoutMs;

        return $this;
    }

    public function setPollIntervalMs(int $pollIntervalMs): self
    {
        $this->pollIntervalMs = $pollIntervalMs;

        return $this;
    }

    /**
     * @throws SmartIdException
     */
    public function poll(string $sessionId): SessionStatus
    {
        $status = $this->connector->getSessionStatus($sessionId, $this->pollTimeoutMs);

        if ($status->isComplete()) {
            $this->validateResult($status);
        }

        return $status;
    }

    /**
     * @throws SmartIdException
     */
    public function pollUntilComplete(string $sessionId, ?int $maxAttempts = null): SessionStatus
    {
        $attempts = 0;

        while ($maxAttempts === null || $attempts < $maxAttempts) {
            $status = $this->poll($sessionId);

            if ($status->isComplete()) {
                return $status;
            }

            $attempts++;
            usleep($this->pollIntervalMs * 1000);
        }

        throw new SessionTimeoutException('Session polling timeout after ' . $maxAttempts . ' attempts');
    }

    /**
     * @throws SmartIdException
     */
    private function validateResult(SessionStatus $status): void
    {
        $result = $status->getResult();

        if ($result === null) {
            throw new SmartIdException('Session completed but no result present');
        }

        if ($result->isOk()) {
            return;
        }

        $endResult = $result->getEndResult();

        if ($result->isUserRefused()) {
            throw new UserRefusedException('User refused: ' . $endResult);
        }

        if ($result->isTimeout()) {
            throw new SessionTimeoutException('Session timed out');
        }

        if ($result->isDocumentUnusable()) {
            throw new DocumentUnusableException('Document is unusable');
        }

        if ($result->isWrongVC()) {
            throw new WrongVerificationCodeException('User selected wrong verification code');
        }

        if ($result->isRequiredInteractionNotSupported()) {
            throw new RequiredInteractionNotSupportedException('Required interaction not supported by app');
        }

        throw new SmartIdException('Session ended with error: ' . $endResult);
    }
}
