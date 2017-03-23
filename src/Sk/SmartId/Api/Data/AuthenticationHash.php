<?php
namespace Sk\SmartId\Api\Data;

class AuthenticationHash extends SignableData
{
  /**
   * @var string
   */
  private $hash;

  /**
   * @param string $hashType
   * @return AuthenticationHash
   */
  public static function generateRandomHash( $hashType )
  {
    $authenticationHash = new AuthenticationHash( self::getRandomBytes() );
    $authenticationHash->setHashType( $hashType );
    $authenticationHash->setHash( $authenticationHash->calculateHash() );
    return $authenticationHash;
  }

  /**
   * @return AuthenticationHash
   */
  public static function generate()
  {
    return self::generateRandomHash( HashType::SHA512 );
  }

  /**
   * @return string
   */
  private static function getRandomBytes()
  {
    return openssl_random_pseudo_bytes( 64 );
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
  public function getHash()
  {
    return $this->hash;
  }
}