<?php
namespace Sk\SmartId\Api\Data;

class SignableHash
{
  /**
   * @var string
   */
  private $hash;

  /**
   * @var string
   */
  private $hashType;

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
   * @param string $hashInBase64
   * @return $this
   */
  public function setHashInBase64( $hashInBase64 )
  {
    $this->hash = base64_decode( $hashInBase64 );
    return $this;
  }

  /**
   * @return string
   */
  public function getHashInBase64()
  {
    return base64_encode( $this->hash );
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
   * @return bool
   */
  public function areFieldsFilled()
  {
    return isset( $this->hashType ) && isset( $this->hash ) && strlen( $this->hashType ) > 0 && strlen( $this->hash ) > 0;
  }
}