<?php
namespace Sk\SmartId\Api\Data;

class DigestCalculator
{
  /**
   * @param string $dataToDigest
   * @param string $hashType
   * @return string
   */
  public static function calculateDigest( $dataToDigest, $hashType )
  {
    return hash( $hashType, $dataToDigest, true );
  }
}