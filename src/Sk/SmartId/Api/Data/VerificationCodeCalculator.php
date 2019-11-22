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
