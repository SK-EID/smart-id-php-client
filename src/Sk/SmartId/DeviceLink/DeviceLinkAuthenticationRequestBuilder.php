<?php

declare(strict_types=1);

namespace Sk\SmartId\DeviceLink;

use Sk\SmartId\Api\SmartIdConnector;
use Sk\SmartId\Enum\CertificateLevel;
use Sk\SmartId\Enum\HashAlgorithm;
use Sk\SmartId\Model\Interaction;
use Sk\SmartId\Util\RpChallengeGenerator;
use Sk\SmartId\Util\VerificationCodeCalculator;

class DeviceLinkAuthenticationRequestBuilder
{
    private SmartIdConnector $connector;

    private string $relyingPartyUUID;

    private string $relyingPartyName;

    private ?string $rpChallenge = null;

    private HashAlgorithm $hashAlgorithm = HashAlgorithm::SHA512;

    private ?CertificateLevel $certificateLevel = null;

    private ?string $nonce = null;

    /** @var string[]|null */
    private ?array $capabilities = null;

    /** @var Interaction[] */
    private array $allowedInteractionsOrder = [];

    public function __construct(
        SmartIdConnector $connector,
        string $relyingPartyUUID,
        string $relyingPartyName,
    ) {
        $this->connector = $connector;
        $this->relyingPartyUUID = $relyingPartyUUID;
        $this->relyingPartyName = $relyingPartyName;
    }

    public function withRpChallenge(string $rpChallenge): self
    {
        $this->rpChallenge = $rpChallenge;

        return $this;
    }

    public function withHashAlgorithm(HashAlgorithm $hashAlgorithm): self
    {
        $this->hashAlgorithm = $hashAlgorithm;

        return $this;
    }

    public function withCertificateLevel(CertificateLevel $certificateLevel): self
    {
        $this->certificateLevel = $certificateLevel;

        return $this;
    }

    public function withNonce(string $nonce): self
    {
        $this->nonce = $nonce;

        return $this;
    }

    /**
     * @param string[] $capabilities
     */
    public function withCapabilities(array $capabilities): self
    {
        $this->capabilities = $capabilities;

        return $this;
    }

    /**
     * @param Interaction[] $interactions
     */
    public function withAllowedInteractionsOrder(array $interactions): self
    {
        $this->allowedInteractionsOrder = $interactions;

        return $this;
    }

    public function initiate(): DeviceLinkAuthenticationSession
    {
        if ($this->rpChallenge === null) {
            $this->rpChallenge = RpChallengeGenerator::generate();
        }

        if (empty($this->allowedInteractionsOrder)) {
            $this->allowedInteractionsOrder = [
                Interaction::verificationCodeChoice(),
            ];
        }

        $request = new DeviceLinkAuthenticationRequest(
            $this->relyingPartyUUID,
            $this->relyingPartyName,
            $this->rpChallenge,
            $this->hashAlgorithm,
            $this->allowedInteractionsOrder,
            $this->certificateLevel,
            $this->nonce,
            $this->capabilities,
        );

        $response = $this->connector->initiateDeviceLinkAuthentication($request);

        $verificationCode = VerificationCodeCalculator::calculateFromRpChallenge(
            $this->rpChallenge,
            $this->hashAlgorithm,
        );

        return new DeviceLinkAuthenticationSession(
            $response,
            $this->rpChallenge,
            $this->relyingPartyName,
            $this->allowedInteractionsOrder,
            $verificationCode,
        );
    }
}
