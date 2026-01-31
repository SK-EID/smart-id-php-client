<?php

declare(strict_types=1);

namespace Sk\SmartId\Tests\Session;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\Api\SmartIdConnector;
use Sk\SmartId\Exception\DocumentUnusableException;
use Sk\SmartId\Exception\RequiredInteractionNotSupportedException;
use Sk\SmartId\Exception\SessionTimeoutException;
use Sk\SmartId\Exception\SmartIdException;
use Sk\SmartId\Exception\UserRefusedException;
use Sk\SmartId\Exception\WrongVerificationCodeException;
use Sk\SmartId\Session\SessionResult;
use Sk\SmartId\Session\SessionStatus;
use Sk\SmartId\Session\SessionStatusPoller;

class SessionStatusPollerTest extends TestCase
{
    #[Test]
    public function pollReturnsStatusFromConnector(): void
    {
        $expectedStatus = new SessionStatus('RUNNING');

        $connector = $this->createMock(SmartIdConnector::class);
        $connector->expects($this->once())
            ->method('getSessionStatus')
            ->with('session-id', 30000)
            ->willReturn($expectedStatus);

        $poller = new SessionStatusPoller($connector);
        $status = $poller->poll('session-id');

        $this->assertSame($expectedStatus, $status);
    }

    #[Test]
    public function pollValidatesCompletedSessionWithOkResult(): void
    {
        $result = new SessionResult('OK', 'DOC123');
        $expectedStatus = new SessionStatus('COMPLETE', $result);

        $connector = $this->createMock(SmartIdConnector::class);
        $connector->method('getSessionStatus')->willReturn($expectedStatus);

        $poller = new SessionStatusPoller($connector);
        $status = $poller->poll('session-id');

        $this->assertSame($expectedStatus, $status);
    }

    #[Test]
    public function pollThrowsUserRefusedExceptionForUserRefusedResult(): void
    {
        $result = new SessionResult('USER_REFUSED');
        $status = new SessionStatus('COMPLETE', $result);

        $connector = $this->createMock(SmartIdConnector::class);
        $connector->method('getSessionStatus')->willReturn($status);

        $poller = new SessionStatusPoller($connector);

        $this->expectException(UserRefusedException::class);
        $this->expectExceptionMessage('User refused');

        $poller->poll('session-id');
    }

    #[Test]
    public function pollThrowsUserRefusedExceptionForUserRefusedVcChoice(): void
    {
        $result = new SessionResult('USER_REFUSED_VC_CHOICE');
        $status = new SessionStatus('COMPLETE', $result);

        $connector = $this->createMock(SmartIdConnector::class);
        $connector->method('getSessionStatus')->willReturn($status);

        $poller = new SessionStatusPoller($connector);

        $this->expectException(UserRefusedException::class);

        $poller->poll('session-id');
    }

    #[Test]
    public function pollThrowsSessionTimeoutExceptionForTimeoutResult(): void
    {
        $result = new SessionResult('TIMEOUT');
        $status = new SessionStatus('COMPLETE', $result);

        $connector = $this->createMock(SmartIdConnector::class);
        $connector->method('getSessionStatus')->willReturn($status);

        $poller = new SessionStatusPoller($connector);

        $this->expectException(SessionTimeoutException::class);
        $this->expectExceptionMessage('Session timed out');

        $poller->poll('session-id');
    }

    #[Test]
    public function pollThrowsDocumentUnusableExceptionForDocumentUnusableResult(): void
    {
        $result = new SessionResult('DOCUMENT_UNUSABLE');
        $status = new SessionStatus('COMPLETE', $result);

        $connector = $this->createMock(SmartIdConnector::class);
        $connector->method('getSessionStatus')->willReturn($status);

        $poller = new SessionStatusPoller($connector);

        $this->expectException(DocumentUnusableException::class);
        $this->expectExceptionMessage('Document is unusable');

        $poller->poll('session-id');
    }

    #[Test]
    public function pollThrowsWrongVerificationCodeExceptionForWrongVcResult(): void
    {
        $result = new SessionResult('WRONG_VC');
        $status = new SessionStatus('COMPLETE', $result);

        $connector = $this->createMock(SmartIdConnector::class);
        $connector->method('getSessionStatus')->willReturn($status);

        $poller = new SessionStatusPoller($connector);

        $this->expectException(WrongVerificationCodeException::class);
        $this->expectExceptionMessage('User selected wrong verification code');

        $poller->poll('session-id');
    }

    #[Test]
    public function pollThrowsRequiredInteractionNotSupportedExceptionForUnsupportedInteraction(): void
    {
        $result = new SessionResult('REQUIRED_INTERACTION_NOT_SUPPORTED_BY_APP');
        $status = new SessionStatus('COMPLETE', $result);

        $connector = $this->createMock(SmartIdConnector::class);
        $connector->method('getSessionStatus')->willReturn($status);

        $poller = new SessionStatusPoller($connector);

        $this->expectException(RequiredInteractionNotSupportedException::class);
        $this->expectExceptionMessage('Required interaction not supported by app');

        $poller->poll('session-id');
    }

    #[Test]
    public function pollThrowsSmartIdExceptionForUnknownErrors(): void
    {
        $result = new SessionResult('SOME_UNKNOWN_ERROR');
        $status = new SessionStatus('COMPLETE', $result);

        $connector = $this->createMock(SmartIdConnector::class);
        $connector->method('getSessionStatus')->willReturn($status);

        $poller = new SessionStatusPoller($connector);

        $this->expectException(SmartIdException::class);
        $this->expectExceptionMessage('Session ended with error: SOME_UNKNOWN_ERROR');

        $poller->poll('session-id');
    }

    #[Test]
    public function pollThrowsExceptionWhenResultIsMissing(): void
    {
        $status = new SessionStatus('COMPLETE');

        $connector = $this->createMock(SmartIdConnector::class);
        $connector->method('getSessionStatus')->willReturn($status);

        $poller = new SessionStatusPoller($connector);

        $this->expectException(SmartIdException::class);
        $this->expectExceptionMessage('Session completed but no result present');

        $poller->poll('session-id');
    }

    #[Test]
    public function pollUntilCompleteReturnsOnFirstCompleteStatus(): void
    {
        $result = new SessionResult('OK');
        $completeStatus = new SessionStatus('COMPLETE', $result);

        $connector = $this->createMock(SmartIdConnector::class);
        $connector->expects($this->once())
            ->method('getSessionStatus')
            ->willReturn($completeStatus);

        $poller = new SessionStatusPoller($connector);
        $status = $poller->pollUntilComplete('session-id');

        $this->assertTrue($status->isComplete());
    }

    #[Test]
    public function pollUntilCompleteRetriesOnRunningStatus(): void
    {
        $runningStatus = new SessionStatus('RUNNING');
        $result = new SessionResult('OK');
        $completeStatus = new SessionStatus('COMPLETE', $result);

        $connector = $this->createMock(SmartIdConnector::class);
        $connector->expects($this->exactly(3))
            ->method('getSessionStatus')
            ->willReturnOnConsecutiveCalls(
                $runningStatus,
                $runningStatus,
                $completeStatus,
            );

        $poller = new SessionStatusPoller($connector);
        $poller->setPollIntervalMs(1); // Speed up test
        $status = $poller->pollUntilComplete('session-id');

        $this->assertTrue($status->isComplete());
    }

    #[Test]
    public function pollUntilCompleteThrowsAfterMaxAttempts(): void
    {
        $runningStatus = new SessionStatus('RUNNING');

        $connector = $this->createMock(SmartIdConnector::class);
        $connector->method('getSessionStatus')->willReturn($runningStatus);

        $poller = new SessionStatusPoller($connector);
        $poller->setPollIntervalMs(1);

        $this->expectException(SessionTimeoutException::class);
        $this->expectExceptionMessage('Session polling timeout after 3 attempts');

        $poller->pollUntilComplete('session-id', 3);
    }

    #[Test]
    public function setPollTimeoutMsReturnsSelf(): void
    {
        $connector = $this->createMock(SmartIdConnector::class);
        $poller = new SessionStatusPoller($connector);

        $result = $poller->setPollTimeoutMs(60000);

        $this->assertSame($poller, $result);
    }

    #[Test]
    public function setPollIntervalMsReturnsSelf(): void
    {
        $connector = $this->createMock(SmartIdConnector::class);
        $poller = new SessionStatusPoller($connector);

        $result = $poller->setPollIntervalMs(2000);

        $this->assertSame($poller, $result);
    }

    #[Test]
    public function pollUsesCustomTimeout(): void
    {
        $connector = $this->createMock(SmartIdConnector::class);
        $connector->expects($this->once())
            ->method('getSessionStatus')
            ->with('session-id', 60000)
            ->willReturn(new SessionStatus('RUNNING'));

        $poller = new SessionStatusPoller($connector);
        $poller->setPollTimeoutMs(60000);
        $poller->poll('session-id');
    }
}
