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

class AuthenticationCertificateExtensions extends PropertyMapper
{
  /**
   * @var string
   */
  private $basicConstraints;

  /**
   * @var string
   */
  private $keyUsage;

  /**
   * @var string
   */
  private $certificatePolicies;

  /**
   * @var string
   */
  private $subjectKeyIdentifier;

  /**
   * @var string
   */
  private $qcStatements;

  /**
   * @var string
   */
  private $authorityKeyIdentifier;

  /**
   * @var string
   */
  private $authorityInfoAccess;

  /**
   * @var string
   */
  private $extendedKeyUsage;

  /**
   * @var string
   */
  private $subjectAltName;

  /**
   * @return string
   */
  public function getBasicConstraints(): string
  {
    return $this->basicConstraints;
  }

  /**
   * @param string $basicConstraints
   * @return $this
   */
  public function setBasicConstraints(string $basicConstraints ): AuthenticationCertificateExtensions
  {
    $this->basicConstraints = $basicConstraints;
    return $this;
  }

  /**
   * @return string
   */
  public function getKeyUsage(): string
  {
    return $this->keyUsage;
  }

  /**
   * @param string $keyUsage
   * @return $this
   */
  public function setKeyUsage(string $keyUsage ): AuthenticationCertificateExtensions
  {
    $this->keyUsage = $keyUsage;
    return $this;
  }

  /**
   * @return string
   */
  public function getCertificatePolicies(): string
  {
    return $this->certificatePolicies;
  }

  /**
   * @param string $certificatePolicies
   * @return $this
   */
  public function setCertificatePolicies(string $certificatePolicies ): AuthenticationCertificateExtensions
  {
    $this->certificatePolicies = $certificatePolicies;
    return $this;
  }

  /**
   * @return string
   */
  public function getSubjectKeyIdentifier(): string
  {
    return $this->subjectKeyIdentifier;
  }

  /**
   * @param string $subjectKeyIdentifier
   * @return $this
   */
  public function setSubjectKeyIdentifier(string $subjectKeyIdentifier ): AuthenticationCertificateExtensions
  {
    $this->subjectKeyIdentifier = $subjectKeyIdentifier;
    return $this;
  }

  /**
   * @return string
   */
  public function getQcStatements(): string
  {
    return $this->qcStatements;
  }

  /**
   * @param string $qcStatements
   * @return $this
   */
  public function setQcStatements(string $qcStatements ): AuthenticationCertificateExtensions
  {
    $this->qcStatements = $qcStatements;
    return $this;
  }

  /**
   * @return string
   */
  public function getAuthorityKeyIdentifier(): string
  {
    return $this->authorityKeyIdentifier;
  }

  /**
   * @param string $authorityKeyIdentifier
   * @return $this
   */
  public function setAuthorityKeyIdentifier(string $authorityKeyIdentifier ): AuthenticationCertificateExtensions
  {
    $this->authorityKeyIdentifier = $authorityKeyIdentifier;
    return $this;
  }

  /**
   * @return string
   */
  public function getAuthorityInfoAccess(): string
  {
    return $this->authorityInfoAccess;
  }

  /**
   * @param string $authorityInfoAccess
   * @return $this
   */
  public function setAuthorityInfoAccess(string $authorityInfoAccess ): AuthenticationCertificateExtensions
  {
    $this->authorityInfoAccess = $authorityInfoAccess;
    return $this;
  }

  /**
   * @return string
   */
  public function getExtendedKeyUsage(): string
  {
    return $this->extendedKeyUsage;
  }

  /**
   * @param string $extendedKeyUsage
   * @return $this
   */
  public function setExtendedKeyUsage(string $extendedKeyUsage ): AuthenticationCertificateExtensions
  {
    $this->extendedKeyUsage = $extendedKeyUsage;
    return $this;
  }

  /**
   * @return string
   */
  public function getSubjectAltName(): string
  {
    return $this->subjectAltName;
  }

  /**
   * @param string $subjectAltName
   * @return $this
   */
  public function setSubjectAltName(string $subjectAltName ): AuthenticationCertificateExtensions
  {
    $this->subjectAltName = $subjectAltName;
    return $this;
  }
}
