<?php
namespace Sk\SmartId\Api\Data;

class SignableDataGenerator
{
  /**
   * @param string $hashType
   * @return SignableData
   */
  public static function generate( $hashType )
  {
    $dataToSign = new SignableData( self::getRandomBytes() );
    $dataToSign
        ->setHashType( $hashType );
    return $dataToSign;
  }

  /**
   * @return string
   */
  private static function getRandomBytes()
  {
    return openssl_random_pseudo_bytes( 64 );
  }
}