<?php

declare(strict_types=1);

namespace Sk\SmartId\DeviceLink;

use Sk\SmartId\Enum\CertificateLevel;
use Sk\SmartId\Enum\HashAlgorithm;
use Sk\SmartId\Enum\SignatureProtocol;
use Sk\SmartId\Model\Interaction;

class DeviceLinkAuthenticationRequest
{
    /**
     * @param Interaction[] $allowedInteractionsOrder
     */
    public function __construct(
        private readonly string $relyingPartyUUID,
        private readonly string $relyingPartyName,
        private readonly string $rpChallenge,
        private readonly HashAlgorithm $hashAlgorithm,
        private readonly array $allowedInteractionsOrder,
        private readonly ?CertificateLevel $certificateLevel = null,
        private readonly ?string $nonce = null,
        private readonly ?array $capabilities = null,
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

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'relyingPartyUUID' => $this->relyingPartyUUID,
            'relyingPartyName' => $this->relyingPartyName,
            'rpChallenge' => $this->rpChallenge,
            'hashAlgorithm' => $this->hashAlgorithm->value,
            'signatureProtocol' => SignatureProtocol::ACSP_V2->value,
            'allowedInteractionsOrder' => array_map(
                fn (Interaction $i) => $i->toArray(),
                $this->allowedInteractionsOrder,
            ),
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
}
