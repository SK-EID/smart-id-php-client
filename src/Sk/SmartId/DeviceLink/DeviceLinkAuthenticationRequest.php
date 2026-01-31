<?php

declare(strict_types=1);

namespace Sk\SmartId\DeviceLink;

use Sk\SmartId\Api\AbstractAuthenticationRequest;
use Sk\SmartId\Enum\CertificateLevel;
use Sk\SmartId\Enum\HashAlgorithm;
use Sk\SmartId\Model\Interaction;

class DeviceLinkAuthenticationRequest extends AbstractAuthenticationRequest
{
    /**
     * @param Interaction[] $allowedInteractionsOrder
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
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = $this->buildBaseArray();
        $data['interactions'] = base64_encode(json_encode($this->mapInteractionsToArray(), JSON_THROW_ON_ERROR));

        return $data;
    }
}
