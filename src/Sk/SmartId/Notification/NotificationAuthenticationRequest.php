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

use Sk\SmartId\Api\AbstractAuthenticationRequest;
use Sk\SmartId\Enum\CertificateLevel;
use Sk\SmartId\Enum\HashAlgorithm;

/**
 * Request data transfer object for push notification-based authentication.
 *
 * This class represents the request payload sent to the Smart-ID API when initiating
 * authentication via push notifications. Push notification flow is used when the user's
 * identity (document number or semantics identifier) is already known to the relying party.
 * The Smart-ID app receives a push notification prompting the user to authenticate.
 *
 * @see NotificationAuthenticationRequestBuilder for building instances of this class
 */
class NotificationAuthenticationRequest extends AbstractAuthenticationRequest
{
    /**
     * @param NotificationInteraction[] $allowedInteractionsOrder
     * @param string[]|null $capabilities
     */
    public function __construct(
        string $relyingPartyUUID,
        string $relyingPartyName,
        string $rpChallenge,
        HashAlgorithm $hashAlgorithm,
        array $allowedInteractionsOrder,
        ?CertificateLevel $certificateLevel = null,
        ?string $nonce = null,
        ?array $capabilities = null,
        bool $shareMdClientIpAddress = false,
    ) {
        parent::__construct(
            $relyingPartyUUID,
            $relyingPartyName,
            $rpChallenge,
            $hashAlgorithm,
            $allowedInteractionsOrder,
            $certificateLevel,
            $nonce,
            $capabilities,
            $shareMdClientIpAddress,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = $this->buildBaseArray();
        $data['interactions'] = NotificationInteraction::encodeInteractionsToBase64($this->allowedInteractionsOrder);
        $data['vcType'] = 'numeric4';

        return $data;
    }
}
