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

namespace Sk\SmartId\Notification;

/**
 * Response from the Smart-ID API after initiating a push notification authentication.
 *
 * This class encapsulates the session ID returned by the API when a notification-based
 * authentication request is successfully initiated. The session ID is used for polling
 * the session status to retrieve the authentication result once the user completes
 * the authentication in their Smart-ID app.
 *
 * @see NotificationAuthenticationSession which wraps this response with additional context
 */
class NotificationAuthenticationResponse
{
    public function __construct(
        private readonly string $sessionID,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $sessionID = $data['sessionID'] ?? null;
        if (!is_string($sessionID)) {
            throw new \InvalidArgumentException('sessionID must be a string');
        }

        return new self($sessionID);
    }

    public function getSessionID(): string
    {
        return $this->sessionID;
    }
}
