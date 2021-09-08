<?php
/*-
 * #%L
 * Smart ID sample PHP client
 * %%
 * Copyright (C) 2018 - 2019 SK ID Solutions AS
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
namespace Sk\SmartId\Api\Data;

class AuthenticationSessionRequest
{
  /**
   * @var string
   */
  private $relyingPartyUUID;

  /**
   * @var string
   */
  private $relyingPartyName;

  /**
   * @var string
   */
  private $networkInterface;

  /**
   * @var string
   */
  private $certificateLevel;

  /**
   * @var string
   */
  private $hash;

  /**
   * @var string
   */
  private $hashType;

  /**
   * @var string
   */
  private $nonce;

    /**
     * @var array
     */
  private $allowedInteractionsOrder;

  /**
   * @return string
   */
  public function getRelyingPartyUUID(): string
  {
    return $this->relyingPartyUUID;
  }

  /**
   * @param string $relyingPartyUUID
   * @return $this
   */
  public function setRelyingPartyUUID(string $relyingPartyUUID ): AuthenticationSessionRequest
  {
    $this->relyingPartyUUID = $relyingPartyUUID;
    return $this;
  }

  /**
   * @return string
   */
  public function getRelyingPartyName(): string
  {
    return $this->relyingPartyName;
  }

  /**
   * @param string $relyingPartyName
   * @return $this
   */
  public function setRelyingPartyName(string $relyingPartyName ): AuthenticationSessionRequest
  {
    $this->relyingPartyName = $relyingPartyName;
    return $this;
  }

  /**
   * @return string
   */
  public function getNetworkInterface(): string
  {
    return $this->networkInterface;
  }

  /**
   * @param string|null $networkInterface
   * @return $this
   */
  public function setNetworkInterface(?string $networkInterface ): AuthenticationSessionRequest
  {
    $this->networkInterface = $networkInterface;
    return $this;
  }

  /**
   * @return string
   */
  public function getCertificateLevel(): ?string
  {
    return $this->certificateLevel;
  }

  /**
   * @param string|null $certificateLevel
   * @return $this
   */
  public function setCertificateLevel(?string $certificateLevel ): AuthenticationSessionRequest
  {
    $this->certificateLevel = $certificateLevel;
    return $this;
  }

  /**
   * @return string
   */
  public function getHash(): string
  {
    return $this->hash;
  }

  /**
   * @param string $hash
   * @return $this
   */
  public function setHash(string $hash ): AuthenticationSessionRequest
  {
    $this->hash = $hash;
    return $this;
  }

  /**
   * @return string
   */
  public function getHashType(): string
  {
    return $this->hashType;
  }

  /**
   * @param string $hashType
   * @return $this
   */
  public function setHashType(string $hashType ): AuthenticationSessionRequest
  {
    $this->hashType = $hashType;
    return $this;
  }

  /**
   * @return string
   */
  public function getNonce(): string
  {
    return $this->nonce;
  }

  /**
   * @param string|null $nonce
   * @return $this
   */
  public function setNonce(?string $nonce ): AuthenticationSessionRequest
  {
    $this->nonce = $nonce;
    return $this;
  }

    /**
     * @return array
     */
    public function getAllowedInteractionsOrder(): array
    {
        return $this->allowedInteractionsOrder;
    }

  /**
   * @param array|null $allowedInteractionsOrder
   * @return AuthenticationSessionRequest
   */
    public function setAllowedInteractionsOrder(?array $allowedInteractionsOrder): AuthenticationSessionRequest
    {
        $this->allowedInteractionsOrder = $allowedInteractionsOrder;
        return $this;
    }



  /**
   * @return array
   */
  public function toArray(): array
  {
    $requiredArray = array(
        'relyingPartyUUID' => $this->relyingPartyUUID,
        'relyingPartyName' => $this->relyingPartyName,
        'hash'             => $this->hash,
        'hashType'         => strtoupper( $this->hashType ),
    );

    if ( isset( $this->certificateLevel ) )
    {
      $requiredArray['certificateLevel'] = $this->certificateLevel;
    }

    if ( isset( $this->allowedInteractionsOrder ) )
    {
      $requiredArray['allowedInteractionsOrder'] = array_map('\Sk\SmartId\Api\Data\AuthenticationSessionRequest::mapInteractionToArray',
        $this->allowedInteractionsOrder);
    }

    if ( isset( $this->nonce ) )
    {
      $requiredArray['nonce'] = $this->nonce;
    }

    if ( isset( $this->networkInterface ) )
    {
      $requiredArray[ 'networkInterface' ] = $this->networkInterface;
    }

    return $requiredArray;
  }

  private static function mapInteractionToArray(Interaction $interaction): array
  {
      return $interaction->toArray();
  }

}
