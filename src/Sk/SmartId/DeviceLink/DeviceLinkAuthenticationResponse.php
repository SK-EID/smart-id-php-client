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

namespace Sk\SmartId\DeviceLink;

class DeviceLinkAuthenticationResponse
{
    public function __construct(
        private readonly string $sessionID,
        private readonly string $sessionToken,
        private readonly string $sessionSecret,
        private readonly string $deviceLinkBase,
    ) {
    }

    /**
     * @param array<string, string> $data
     * @throws \InvalidArgumentException
     */
    public static function fromArray(array $data): self
    {
        $requiredKeys = ['sessionID', 'sessionToken', 'sessionSecret', 'deviceLinkBase'];
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $data)) {
                throw new \InvalidArgumentException("Missing required key: {$key}");
            }
        }

        return new self(
            $data['sessionID'],
            $data['sessionToken'],
            $data['sessionSecret'],
            $data['deviceLinkBase'],
        );
    }

    public function getSessionID(): string
    {
        return $this->sessionID;
    }

    public function getSessionToken(): string
    {
        return $this->sessionToken;
    }

    public function getSessionSecret(): string
    {
        return $this->sessionSecret;
    }

    public function getDeviceLinkBase(): string
    {
        return $this->deviceLinkBase;
    }
}
