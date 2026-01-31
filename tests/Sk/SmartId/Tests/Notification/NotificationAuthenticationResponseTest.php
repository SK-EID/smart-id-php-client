<?php

declare(strict_types=1);

namespace Sk\SmartId\Tests\Notification;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\Notification\NotificationAuthenticationResponse;

class NotificationAuthenticationResponseTest extends TestCase
{
    #[Test]
    public function fromArrayCreatesResponse(): void
    {
        $response = NotificationAuthenticationResponse::fromArray([
            'sessionID' => 'test-session-id',
        ]);

        $this->assertSame('test-session-id', $response->getSessionID());
    }

    #[Test]
    public function constructorSetsSessionId(): void
    {
        $response = new NotificationAuthenticationResponse('session-123');

        $this->assertSame('session-123', $response->getSessionID());
    }
}
