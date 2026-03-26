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

namespace Sk\SmartId\Tests\Notification;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\Notification\NotificationAuthenticationResponse;
use Sk\SmartId\Notification\NotificationAuthenticationSession;

class NotificationAuthenticationSessionTest extends TestCase
{
    #[Test]
    public function getSessionIdReturnsCorrectValue(): void
    {
        $response = new NotificationAuthenticationResponse('session-123');
        $session = new NotificationAuthenticationSession(
            $response,
            'rpChallenge',
            '1234',
            'dGVzdA==',
        );

        $this->assertSame('session-123', $session->getSessionId());
    }

    #[Test]
    public function getVerificationCodeReturnsCorrectValue(): void
    {
        $response = new NotificationAuthenticationResponse('session-123');
        $session = new NotificationAuthenticationSession(
            $response,
            'rpChallenge',
            '5678',
            'dGVzdA==',
        );

        $this->assertSame('5678', $session->getVerificationCode());
    }

    #[Test]
    public function getRpChallengeReturnsCorrectValue(): void
    {
        $response = new NotificationAuthenticationResponse('session-123');
        $rpChallenge = base64_encode('test-challenge');
        $session = new NotificationAuthenticationSession(
            $response,
            $rpChallenge,
            '1234',
            'dGVzdA==',
        );

        $this->assertSame($rpChallenge, $session->getRpChallenge());
    }

    #[Test]
    public function getResponseReturnsCorrectValue(): void
    {
        $response = new NotificationAuthenticationResponse('session-123');
        $session = new NotificationAuthenticationSession(
            $response,
            'rpChallenge',
            '1234',
            'dGVzdA==',
        );

        $this->assertSame($response, $session->getResponse());
    }

    #[Test]
    public function getInteractionsBase64ReturnsCorrectValue(): void
    {
        $response = new NotificationAuthenticationResponse('session-123');
        $interactionsBase64 = 'W3sidHlwZSI6ImRpc3BsYXlUZXh0QW5kUElOIiwiZGlzcGxheVRleHQ2MCI6IlRlc3QifV0=';
        $session = new NotificationAuthenticationSession(
            $response,
            'rpChallenge',
            '1234',
            $interactionsBase64,
        );

        $this->assertSame($interactionsBase64, $session->getInteractionsBase64());
    }
}
