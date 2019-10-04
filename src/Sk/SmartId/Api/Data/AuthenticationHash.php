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

  /**
   * @return string
   */
  public function calculateVerificationCode()
  {
    return VerificationCodeCalculator::calculate( $this->hash );
  }
}
