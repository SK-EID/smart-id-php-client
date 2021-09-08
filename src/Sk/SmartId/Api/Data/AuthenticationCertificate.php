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

class AuthenticationCertificate extends PropertyMapper
{
  /**
   * @var string
   */
  private $name;

  /**
   * @var AuthenticationCertificateSubject
   */
  private $subject;

  /**
   * @var string
   */
  private $hash;

  /**
   * @var AuthenticationCertificateIssuer
   */
  private $issuer;

  /**
   * @var int
   */
  private $version;

  /**
   * @var string
   */
  private $serialNumber;

  /**
   * @var string
   */
  private $serialNumberHex;

  /**
   * @var string
   */
  private $validFrom;

  /**
   * @var string
   */
  private $validTo;

  /**
   * @var int
   */
  private $validFromTimeT;

  /**
   * @var int
   */
  private $validToTimeT;

  /**
   * @var string
   */
  private $signatureTypeSN;

  /**
   * @var string
   */
  private $signatureTypeLN;

  /**
   * @var int
   */
  private $signatureTypeNID;

  /**
   * @var array
   */
  private $purposes;

  /**
   * @var AuthenticationCertificateExtensions
   */
  private $extensions;

  /**
   * @return string
   */
  public function getName(): string
  {
    return $this->name;
  }

  /**
   * @param string $name
   * @return $this
   */
  public function setName(string $name ): AuthenticationCertificate
  {
    $this->name = $name;
    return $this;
  }

  /**
   * @return AuthenticationCertificateSubject
   */
  public function getSubject(): AuthenticationCertificateSubject
  {
    return $this->subject;
  }

    /**
     * @param AuthenticationCertificateSubject|null $subject
     * @return $this
     */
  public function setSubject( AuthenticationCertificateSubject $subject = null ): AuthenticationCertificate
  {
    $this->subject = $subject;
    return $this;
  }

  /**
   * @return string
   */
  public function getHash(): string
  {
    return $this->hash;
  }

  /**
   * @param string $hash
   * @return $this
   */
  public function setHash(string $hash ): AuthenticationCertificate
  {
    $this->hash = $hash;
    return $this;
  }

  /**
   * @return AuthenticationCertificateIssuer
   */
  public function getIssuer(): AuthenticationCertificateIssuer
  {
    return $this->issuer;
  }

    /**
     * @param AuthenticationCertificateIssuer|null $issuer
     * @return $this
     */
  public function setIssuer( AuthenticationCertificateIssuer $issuer = null ): AuthenticationCertificate
  {
    $this->issuer = $issuer;
    return $this;
  }

  /**
   * @return int
   */
  public function getVersion(): int
  {
    return $this->version;
  }

  /**
   * @param int $version
   * @return $this
   */
  public function setVersion(int $version ): AuthenticationCertificate
  {
    $this->version = $version;
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
  public function setSerialNumber(string $serialNumber ): AuthenticationCertificate
  {
    $this->serialNumber = $serialNumber;
    return $this;
  }

  /**
   * @return string
   */
  public function getSerialNumberHex(): string
  {
    return $this->serialNumberHex;
  }

  /**
   * @param string $serialNumberHex
   * @return $this
   */
  public function setSerialNumberHex(string $serialNumberHex ): AuthenticationCertificate
  {
    $this->serialNumberHex = $serialNumberHex;
    return $this;
  }

  /**
   * @return string
   */
  public function getValidFrom(): string
  {
    return $this->validFrom;
  }

  /**
   * @param string $validFrom
   * @return $this
   */
  public function setValidFrom(string $validFrom ): AuthenticationCertificate
  {
    $this->validFrom = $validFrom;
    return $this;
  }

  /**
   * @return string
   */
  public function getValidTo(): string
  {
    return $this->validTo;
  }

  /**
   * @param string $validTo
   * @return $this
   */
  public function setValidTo(string $validTo ): AuthenticationCertificate
  {
    $this->validTo = $validTo;
    return $this;
  }

  /**
   * @return int
   */
  public function getValidFromTimeT(): int
  {
    return $this->validFromTimeT;
  }

  /**
   * @param int $validFromTimeT
   * @return $this
   */
  public function setValidFromTimeT(int $validFromTimeT ): AuthenticationCertificate
  {
    $this->validFromTimeT = $validFromTimeT;
    return $this;
  }

  /**
   * @return int
   */
  public function getValidToTimeT(): int
  {
    return $this->validToTimeT;
  }

  /**
   * @param int $validToTimeT
   * @return $this
   */
  public function setValidToTimeT(int $validToTimeT ): AuthenticationCertificate
  {
    $this->validToTimeT = $validToTimeT;
    return $this;
  }

  /**
   * @return string
   */
  public function getSignatureTypeSN(): string
  {
    return $this->signatureTypeSN;
  }

  /**
   * @param string $signatureTypeSN
   * @return $this
   */
  public function setSignatureTypeSN(string $signatureTypeSN ): AuthenticationCertificate
  {
    $this->signatureTypeSN = $signatureTypeSN;
    return $this;
  }

  /**
   * @return string
   */
  public function getSignatureTypeLN()
  {
    return $this->signatureTypeLN;
  }

  /**
   * @param string $signatureTypeLN
   * @return $this
   */
  public function setSignatureTypeLN(string $signatureTypeLN ): AuthenticationCertificate
  {
    $this->signatureTypeLN = $signatureTypeLN;
    return $this;
  }

  /**
   * @return int
   */
  public function getSignatureTypeNID(): int
  {
    return $this->signatureTypeNID;
  }

  /**
   * @param int $signatureTypeNID
   * @return $this
   */
  public function setSignatureTypeNID(int $signatureTypeNID ): AuthenticationCertificate
  {
    $this->signatureTypeNID = $signatureTypeNID;
    return $this;
  }

  /**
   * @return array
   */
  public function getPurposes(): array
  {
    return $this->purposes;
  }

  /**
   * @param array $purposes
   * @return $this
   */
  public function setPurposes( array $purposes = array() ): AuthenticationCertificate
  {
    $this->purposes = $purposes;
    return $this;
  }

  /**
   * @return AuthenticationCertificateExtensions
   */
  public function getExtensions(): AuthenticationCertificateExtensions
  {
    return $this->extensions;
  }

    /**
     * @param AuthenticationCertificateExtensions|null $extensions
     * @return $this
     */
  public function setExtensions( AuthenticationCertificateExtensions $extensions = null ): AuthenticationCertificate
  {
    $this->extensions = $extensions;
    return $this;
  }
}
