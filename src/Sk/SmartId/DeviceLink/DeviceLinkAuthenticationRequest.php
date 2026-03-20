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

use Sk\SmartId\Api\AbstractAuthenticationRequest;
use Sk\SmartId\Enum\CertificateLevel;
use Sk\SmartId\Enum\HashAlgorithm;

/**
 * Request data transfer object for device link-based authentication.
 *
 * This class represents the request payload sent to the Smart-ID API when initiating
 * authentication via device linking (QR code or web2app). Device link flow
 * is used when the user's identity is NOT known beforehand - the user scans a QR code
 * or clicks a link to initiate authentication from their Smart-ID app.
 *
 * Unlike notification-based authentication, device link does not require a document
 * number or semantics identifier upfront, making it suitable for anonymous or
 * first-time authentication scenarios.
 *
 * @see DeviceLinkAuthenticationRequestBuilder for building instances of this class
 */
class DeviceLinkAuthenticationRequest extends AbstractAuthenticationRequest
{
    /**
     * @param DeviceLinkInteraction[] $allowedInteractionsOrder
     * @param string[]|null $capabilities
     * @param string|null $initialCallbackUrl Callback URL for Web2App flows (must be HTTPS)
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
        private readonly ?string $initialCallbackUrl = null,
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

    public function getInitialCallbackUrl(): ?string
    {
        return $this->initialCallbackUrl;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = $this->buildBaseArray();
        $data['interactions'] = base64_encode(json_encode($this->mapInteractionsToArray(), JSON_THROW_ON_ERROR));

        if ($this->initialCallbackUrl !== null) {
            $data['initialCallbackUrl'] = $this->initialCallbackUrl;
        }

        return $data;
    }
}
