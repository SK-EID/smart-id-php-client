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

class AuthenticationCertificateSubject extends PropertyMapper
{
  /**
   * @var string
   */
  private $C;

  /**
   * @var string
   */
  private $O;

  /**
   * @var string
   */
  private $OU;

  /**
   * @var string
   */
  private $CN;

  /**
   * @var string
   */
  private $SN;

  /**
   * @var string
   */
  private $GN;

  /**
   * @var string
   */
  private $serialNumber;

  /**
   * @return string
   */
  public function getC(): string
  {
    return $this->C;
  }

  /**
   * @param string $C
   * @return $this
   */
  public function setC(string $C ): AuthenticationCertificateSubject
  {
    $this->C = $C;
    return $this;
  }

  /**
   * @return string
   */
  public function getO(): string
  {
    return $this->O;
  }

  /**
   * @param string $O
   * @return $this
   */
  public function setO(string $O ): AuthenticationCertificateSubject
  {
    $this->O = $O;
    return $this;
  }

  /**
   * @return string
   */
  public function getOU(): string
  {
    return $this->OU;
  }

  /**
   * @param string $OU
   * @return $this
   */
  public function setOU(string $OU ): AuthenticationCertificateSubject
  {
    $this->OU = $OU;
    return $this;
  }

  /**
   * @return string
   */
  public function getCN(): string
  {
    return $this->CN;
  }

  /**
   * @param string $CN
   * @return $this
   */
  public function setCN(string $CN ): AuthenticationCertificateSubject
  {
    $this->CN = $CN;
    return $this;
  }

  /**
   * @return string
   */
  public function getSN(): string
  {
    return $this->SN;
  }

  /**
   * @param string $SN
   * @return $this
   */
  public function setSN(string $SN ): AuthenticationCertificateSubject
  {
    $this->SN = $SN;
    return $this;
  }

  /**
   * @return string
   */
  public function getGN(): string
  {
    return $this->GN;
  }

  /**
   * @param string $GN
   * @return $this
   */
  public function setGN(string $GN ): AuthenticationCertificateSubject
  {
    $this->GN = $GN;
    return $this;
  }

  /**
   * @return string
   */
  public function getSerialNumber(): string
  {
    return $this->serialNumber;
  }

  /**
   * @param string $serialNumber
   * @return $this
   */
  public function setSerialNumber(string $serialNumber ): AuthenticationCertificateSubject
  {
    $this->serialNumber = $serialNumber;
    return $this;
  }
}
