<?php
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
    return array(
        'relyingPartyUUID' => $this->relyingPartyUUID,
        'relyingPartyName' => $this->relyingPartyName,
        'certificateLevel' => $this->certificateLevel,
        'hash'             => $this->hash,
        'hashType'         => strtoupper( $this->hashType ),
        'displayText'      => $this->displayText,
        'nonce'            => $this->nonce,
    );
  }
}