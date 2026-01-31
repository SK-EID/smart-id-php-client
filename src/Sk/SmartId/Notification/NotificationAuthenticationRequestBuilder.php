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

use Sk\SmartId\Api\SmartIdConnector;
use Sk\SmartId\Enum\CertificateLevel;
use Sk\SmartId\Enum\HashAlgorithm;
use Sk\SmartId\Model\Interaction;
use Sk\SmartId\Model\SemanticsIdentifier;
use Sk\SmartId\Util\RpChallengeGenerator;
use Sk\SmartId\Util\VerificationCodeCalculator;

class NotificationAuthenticationRequestBuilder
{
    private SmartIdConnector $connector;

    private string $relyingPartyUUID;

    private string $relyingPartyName;

    private ?string $documentNumber = null;

    private ?SemanticsIdentifier $semanticsIdentifier = null;

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

    public function withDocumentNumber(string $documentNumber): self
    {
        $this->documentNumber = $documentNumber;
        $this->semanticsIdentifier = null;

        return $this;
    }

    public function withSemanticsIdentifier(SemanticsIdentifier $semanticsIdentifier): self
    {
        $this->semanticsIdentifier = $semanticsIdentifier;
        $this->documentNumber = null;

        return $this;
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

    public function initiate(): NotificationAuthenticationSession
    {
        if ($this->documentNumber === null && $this->semanticsIdentifier === null) {
            throw new \InvalidArgumentException('Either documentNumber or semanticsIdentifier must be set');
        }

        if ($this->rpChallenge === null) {
            $this->rpChallenge = RpChallengeGenerator::generate();
        }

        if (empty($this->allowedInteractionsOrder)) {
            $this->allowedInteractionsOrder = [
                Interaction::verificationCodeChoice(),
            ];
        }

        $request = new NotificationAuthenticationRequest(
            $this->relyingPartyUUID,
            $this->relyingPartyName,
            $this->rpChallenge,
            $this->hashAlgorithm,
            $this->allowedInteractionsOrder,
            $this->certificateLevel,
            $this->nonce,
            $this->capabilities,
        );

        $response = $this->connector->initiateNotificationAuthentication(
            $request,
            $this->documentNumber,
            $this->semanticsIdentifier !== null ? (string) $this->semanticsIdentifier : null,
        );

        $verificationCode = VerificationCodeCalculator::calculateFromRpChallenge(
            $this->rpChallenge,
            $this->hashAlgorithm,
        );

        return new NotificationAuthenticationSession(
            $response,
            $this->rpChallenge,
            $verificationCode,
        );
    }
}
