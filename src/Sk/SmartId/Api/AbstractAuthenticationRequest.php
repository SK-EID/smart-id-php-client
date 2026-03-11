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

namespace Sk\SmartId\Api;

use Sk\SmartId\Enum\CertificateLevel;
use Sk\SmartId\Enum\HashAlgorithm;
use Sk\SmartId\Enum\SignatureProtocol;
use Sk\SmartId\Model\Interaction;

abstract class AbstractAuthenticationRequest
{
    /**
     * @param Interaction[] $allowedInteractionsOrder
     * @param string[]|null $capabilities
     */
    public function __construct(
        protected readonly string $relyingPartyUUID,
        protected readonly string $relyingPartyName,
        protected readonly string $rpChallenge,
        protected readonly HashAlgorithm $hashAlgorithm,
        protected readonly array $allowedInteractionsOrder,
        protected readonly ?CertificateLevel $certificateLevel = null,
        protected readonly ?string $nonce = null,
        protected readonly ?array $capabilities = null,
        protected readonly bool $shareMdClientIpAddress = false,
    ) {
    }

    public function getRelyingPartyUUID(): string
    {
        return $this->relyingPartyUUID;
    }

    public function getRelyingPartyName(): string
    {
        return $this->relyingPartyName;
    }

    public function getRpChallenge(): string
    {
        return $this->rpChallenge;
    }

    public function getHashAlgorithm(): HashAlgorithm
    {
        return $this->hashAlgorithm;
    }

    /**
     * @return Interaction[]
     */
    public function getAllowedInteractionsOrder(): array
    {
        return $this->allowedInteractionsOrder;
    }

    public function getCertificateLevel(): ?CertificateLevel
    {
        return $this->certificateLevel;
    }

    public function getNonce(): ?string
    {
        return $this->nonce;
    }

    /**
     * @return string[]|null
     */
    public function getCapabilities(): ?array
    {
        return $this->capabilities;
    }

    public function getShareMdClientIpAddress(): bool
    {
        return $this->shareMdClientIpAddress;
    }

    /**
     * @return array<string, mixed>
     */
    abstract public function toArray(): array;

    /**
     * @return array<string, mixed>
     */
    protected function buildBaseArray(): array
    {
        $data = [
            'relyingPartyUUID' => $this->relyingPartyUUID,
            'relyingPartyName' => $this->relyingPartyName,
            'signatureProtocol' => SignatureProtocol::ACSP_V2->value,
            'signatureProtocolParameters' => [
                'rpChallenge' => $this->rpChallenge,
                'signatureAlgorithm' => 'rsassa-pss',
                'signatureAlgorithmParameters' => [
                    'hashAlgorithm' => $this->hashAlgorithm->value,
                ],
            ],
        ];

        if ($this->certificateLevel !== null) {
            $data['certificateLevel'] = $this->certificateLevel->value;
        }

        if ($this->nonce !== null) {
            $data['nonce'] = $this->nonce;
        }

        if ($this->capabilities !== null) {
            $data['capabilities'] = $this->capabilities;
        }

        if ($this->shareMdClientIpAddress) {
            $data['requestProperties'] = [
                'shareMdClientIpAddress' => true,
            ];
        }

        return $data;
    }

    /**
     * @return list<array<string, string>>
     */
    protected function mapInteractionsToArray(): array
    {
        return array_values(array_map(
            fn (Interaction $i) => $i->toArray(),
            $this->allowedInteractionsOrder,
        ));
    }
}
