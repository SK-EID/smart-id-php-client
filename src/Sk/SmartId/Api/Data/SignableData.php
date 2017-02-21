<?php
namespace Sk\SmartId\Api\Data;

class SignableData
{
  /**
   * @var string
   */
  private $dataToSign;

  /**
   * @var string
   */
  private $hashType = HashType::SHA512;

  /**
   * @param string $dataToSign
   */
  public function __construct( $dataToSign )
  {
    $this->dataToSign = $dataToSign;
  }

  /**
   * @return string
   */
  public function calculateHashInBase64()
  {
    $digest = $this->calculateHash();
    return base64_encode( $digest );
  }

  /**
   * @return string
   */
  public function calculateHash()
  {
    return DigestCalculator::calculateDigest( $this->dataToSign, $this->hashType );
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
  public function getHashType()
  {
    return $this->hashType;
  }
}