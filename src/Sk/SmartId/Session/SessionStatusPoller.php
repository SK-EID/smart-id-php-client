<?php

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

        if (str_starts_with($endResult, 'USER_REFUSED')) {
            throw new UserRefusedException('User refused: ' . $endResult);
        }

        if ($endResult === SessionResult::END_RESULT_TIMEOUT) {
            throw new SessionTimeoutException('Session timed out');
        }

        if ($endResult === SessionResult::END_RESULT_DOCUMENT_UNUSABLE) {
            throw new DocumentUnusableException('Document is unusable');
        }

        if ($endResult === SessionResult::END_RESULT_WRONG_VC) {
            throw new WrongVerificationCodeException('User selected wrong verification code');
        }

        if ($endResult === SessionResult::END_RESULT_REQUIRED_INTERACTION_NOT_SUPPORTED_BY_APP) {
            throw new RequiredInteractionNotSupportedException('Required interaction not supported by app');
        }

        throw new SmartIdException('Session ended with error: ' . $endResult);
    }
}
