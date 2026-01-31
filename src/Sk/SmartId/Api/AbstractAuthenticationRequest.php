<?php

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

        return $data;
    }

    /**
     * @return array<int, array<string, string>>
     */
    protected function mapInteractionsToArray(): array
    {
        return array_map(
            fn (Interaction $i) => $i->toArray(),
            $this->allowedInteractionsOrder,
        );
    }
}
