<?php
namespace Sk\SmartId\Api\Data;

class VerificationCodeCalculator
{
  /**
   * The Verification Code (VC) is computed as:
   * <p/>
   * integer(SHA256(hash)[−2:−1]) mod 10000
   * <p/>
   * where we take SHA256 result, extract 2 rightmost bytes from it,
   * interpret them as a big-endian unsigned short and take the last 4 digits in decimal for display.
   * <p/>
   * SHA256 is always used here, no matter what was the algorithm used to calculate hash.
   *
   * @param string $documentHash
   * @return string
   */
  public static function calculate( $documentHash )
  {
    $digest = DigestCalculator::calculateDigest( $documentHash, HashType::SHA256 );
    $twoRightmostBytes = substr( $digest, -2 );
    $positiveInteger = unpack( 'n*', $twoRightmostBytes )[1];
    return str_pad( substr( ( $positiveInteger % 10000 ), -4 ), 4, '0', STR_PAD_LEFT );
  }
}