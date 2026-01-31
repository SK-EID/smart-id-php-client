<?php

declare(strict_types=1);

namespace Sk\SmartId\Session;

class SessionResult
{
    public const END_RESULT_OK = 'OK';

    public const END_RESULT_USER_REFUSED = 'USER_REFUSED';

    public const END_RESULT_TIMEOUT = 'TIMEOUT';

    public const END_RESULT_DOCUMENT_UNUSABLE = 'DOCUMENT_UNUSABLE';

    public const END_RESULT_WRONG_VC = 'WRONG_VC';

    public const END_RESULT_REQUIRED_INTERACTION_NOT_SUPPORTED_BY_APP = 'REQUIRED_INTERACTION_NOT_SUPPORTED_BY_APP';

    public const END_RESULT_USER_REFUSED_CERT_CHOICE = 'USER_REFUSED_CERT_CHOICE';

    public const END_RESULT_USER_REFUSED_DISPLAYTEXTANDPIN = 'USER_REFUSED_DISPLAYTEXTANDPIN';

    public const END_RESULT_USER_REFUSED_VC_CHOICE = 'USER_REFUSED_VC_CHOICE';

    public const END_RESULT_USER_REFUSED_CONFIRMATIONMESSAGE = 'USER_REFUSED_CONFIRMATIONMESSAGE';

    public const END_RESULT_USER_REFUSED_CONFIRMATIONMESSAGE_WITH_VC_CHOICE = 'USER_REFUSED_CONFIRMATIONMESSAGE_WITH_VC_CHOICE';

    public function __construct(
        private readonly string $endResult,
        private readonly ?string $documentNumber = null,
        private readonly ?string $interactionFlowUsed = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['endResult'],
            $data['documentNumber'] ?? null,
            $data['interactionFlowUsed'] ?? null,
        );
    }

    public function getEndResult(): string
    {
        return $this->endResult;
    }

    public function getDocumentNumber(): ?string
    {
        return $this->documentNumber;
    }

    public function getInteractionFlowUsed(): ?string
    {
        return $this->interactionFlowUsed;
    }

    public function isOk(): bool
    {
        return $this->endResult === self::END_RESULT_OK;
    }
}
