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

  /**
   * @return bool
   */
  public function areFieldsFilled()
  {
    return isset( $this->hashType )
           && isset( $this->dataToSign )
           && strlen( $this->hashType ) > 0
           && strlen( $this->dataToSign ) > 0;
  }

  /**
   * @return string
   */
  public function getDataToSign()
  {
    return $this->dataToSign;
  }
}
