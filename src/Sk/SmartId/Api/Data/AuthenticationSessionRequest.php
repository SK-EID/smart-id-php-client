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
  private $displayText;

  /**
   * @var string
   */
  private $nonce;

  /**
   * @return string
   */
  public function getRelyingPartyUUID()
  {
    return $this->relyingPartyUUID;
  }

  /**
   * @param string $relyingPartyUUID
   * @return $this
   */
  public function setRelyingPartyUUID( $relyingPartyUUID )
  {
    $this->relyingPartyUUID = $relyingPartyUUID;
    return $this;
  }

  /**
   * @return string
   */
  public function getRelyingPartyName()
  {
    return $this->relyingPartyName;
  }

  /**
   * @param string $relyingPartyName
   * @return $this
   */
  public function setRelyingPartyName( $relyingPartyName )
  {
    $this->relyingPartyName = $relyingPartyName;
    return $this;
  }

  /**
   * @return string
   */
  public function getNetworkInterface()
  {
    return $this->networkInterface;
  }

  /**
   * @param string $networkInterface
   * @return $this
   */
  public function setNetworkInterface( $networkInterface )
  {
    $this->networkInterface = $networkInterface;
    return $this;
  }

  /**
   * @return string
   */
  public function getCertificateLevel()
  {
    return $this->certificateLevel;
  }

  /**
   * @param string $certificateLevel
   * @return $this
   */
  public function setCertificateLevel( $certificateLevel )
  {
    $this->certificateLevel = $certificateLevel;
    return $this;
  }

  /**
   * @return string
   */
  public function getHash()
  {
    return $this->hash;
  }

  /**
   * @param string $hash
   * @return $this
   */
  public function setHash( $hash )
  {
    $this->hash = $hash;
    return $this;
  }

  /**
   * @return string
   */
  public function getHashType()
  {
    return $this->hashType;
  }

  /**
   * @param string $hashType
   * @return $this
   */
  public function setHashType( $hashType )
  {
    $this->hashType = $hashType;
    return $this;
  }

  /**
   * @return string
   */
  public function getDisplayText()
  {
    return $this->displayText;
  }

  /**
   * @param string $displayText
   * @return $this
   */
  public function setDisplayText( $displayText )
  {
    $this->displayText = $displayText;
    return $this;
  }

  /**
   * @return string
   */
  public function getNonce()
  {
    return $this->nonce;
  }

  /**
   * @param string $nonce
   * @return $this
   */
  public function setNonce( $nonce )
  {
    $this->nonce = $nonce;
    return $this;
  }

  /**
   * @return array
   */
  public function toArray()
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

    if ( isset( $this->displayText ) )
    {
      $requiredArray['displayText'] = $this->displayText;
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
}
