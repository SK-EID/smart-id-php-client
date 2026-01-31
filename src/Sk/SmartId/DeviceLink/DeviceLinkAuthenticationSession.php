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

use Sk\SmartId\Model\Interaction;

class DeviceLinkAuthenticationSession
{
    private readonly float $createdAt;

    /**
     * @param Interaction[] $interactions
     */
    public function __construct(
        private readonly DeviceLinkAuthenticationResponse $response,
        private readonly string $rpChallenge,
        private readonly string $rpName,
        private readonly array $interactions,
        private readonly string $verificationCode,
    ) {
        $this->createdAt = microtime(true);
    }

    public function getSessionId(): string
    {
        return $this->response->getSessionID();
    }

    public function getVerificationCode(): string
    {
        return $this->verificationCode;
    }

    public function getElapsedSeconds(): int
    {
        return (int) floor(microtime(true) - $this->createdAt);
    }

    public function createDeviceLinkBuilder(): DeviceLinkBuilder
    {
        return new DeviceLinkBuilder(
            $this->response,
            $this->rpChallenge,
            $this->rpName,
            $this->interactions,
        );
    }

    public function buildQrCodeUrl(?int $elapsedSeconds = null): string
    {
        return $this->buildUrlWithElapsed($elapsedSeconds)->buildQrCodeUrl();
    }

    public function buildWeb2AppUrl(?int $elapsedSeconds = null): string
    {
        return $this->buildUrlWithElapsed($elapsedSeconds)->buildWeb2AppUrl();
    }

    public function buildApp2AppUrl(?int $elapsedSeconds = null): string
    {
        return $this->buildUrlWithElapsed($elapsedSeconds)->buildApp2AppUrl();
    }

    private function buildUrlWithElapsed(?int $elapsedSeconds): DeviceLinkBuilder
    {
        $builder = $this->createDeviceLinkBuilder();

        return $builder->withElapsedSeconds($elapsedSeconds ?? $this->getElapsedSeconds());
    }

    public function getRpChallenge(): string
    {
        return $this->rpChallenge;
    }

    public function getResponse(): DeviceLinkAuthenticationResponse
    {
        return $this->response;
    }
}
