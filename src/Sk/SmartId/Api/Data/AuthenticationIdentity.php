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

class AuthenticationIdentity
{
  /**
   * @var string
   */
  private $givenName;

  /**
   * @var string
   */
  private $surName;

  /**
   * @var string National Identity number with prefix. Example value: 'PNOEE-10101010005'
   */
  private $identityCode;

  /**
   * @var string National Identity number without prefix. Example value: '10101010005'
   */
  private $identityNumber;

  /**
   * @var string
   */
  private $country;

  /**
   * @var string
   */
  private $authCertificate;

  /**
   * @var ?\DateTimeImmutable
   */
  private $dateOfBirth;

  /**
   * @return string
   */
  public function getGivenName(): string
  {
    return $this->givenName;
  }

  /**
   * @param string $givenName
   * @return $this
   */
  public function setGivenName(string $givenName ): AuthenticationIdentity
  {
    $this->givenName = $givenName;
    return $this;
  }

  /**
   * @return string
   */
  public function getSurName(): string
  {
    return $this->surName;
  }

  /**
   * @param string $surName
   * @return $this
   */
  public function setSurName(string $surName ): AuthenticationIdentity
  {
    $this->surName = $surName;
    return $this;
  }

  /**
   * @return string National Identity number with prefix. Example value: 'PNOEE-10101010005'
   */
  public function getIdentityCode(): string
  {
    return $this->identityCode;
  }

  /**
   * @param string $identityCode
   * @return $this
   */
  public function setIdentityCode(string $identityCode ): AuthenticationIdentity
  {
    $this->identityCode = $identityCode;
    return $this;
  }

  /**
   * @return string National Identity number without prefix. Example value: '10101010005'
   */
  public function getIdentityNumber(): string
  {
    return $this->identityNumber;
  }

  /**
   * @param string $identityNumber
   */
  public function setIdentityNumber(string $identityNumber): void
  {
    $this->identityNumber = $identityNumber;
  }

  /**
   * @return string
   */
  public function getCountry(): string
  {
    return $this->country;
  }

  /**
   * @param string $country
   * @return $this
   */
  public function setCountry(string $country ): AuthenticationIdentity
  {
    $this->country = $country;
    return $this;
  }

  /**
   * @return string
   */
  public function getAuthCertificate(): string
  {
    return $this->authCertificate;
  }

  /**
   * @param string $authCertificate
   */
  public function setAuthCertificate(string $authCertificate): void
  {
    $this->authCertificate = $authCertificate;
  }

  /**
   * @return \DateTimeImmutable
   */
  public function getDateOfBirth(): ?\DateTimeImmutable
  {
    return $this->dateOfBirth;
  }

  /**
   * @param \DateTimeImmutable $dateOfBirth
   */
  public function setDateOfBirth(?\DateTimeImmutable $dateOfBirth): void
  {
    $this->dateOfBirth = $dateOfBirth;
  }



}
