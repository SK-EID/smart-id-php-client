<?php
namespace Sk\SmartId\Api\Data;

class AuthenticationCertificateExtensions extends AbstractData
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
  public function getBasicConstraints()
  {
    return $this->basicConstraints;
  }

  /**
   * @param string $basicConstraints
   * @return $this
   */
  public function setBasicConstraints( $basicConstraints )
  {
    $this->basicConstraints = $basicConstraints;
    return $this;
  }

  /**
   * @return string
   */
  public function getKeyUsage()
  {
    return $this->keyUsage;
  }

  /**
   * @param string $keyUsage
   * @return $this
   */
  public function setKeyUsage( $keyUsage )
  {
    $this->keyUsage = $keyUsage;
    return $this;
  }

  /**
   * @return string
   */
  public function getCertificatePolicies()
  {
    return $this->certificatePolicies;
  }

  /**
   * @param string $certificatePolicies
   * @return $this
   */
  public function setCertificatePolicies( $certificatePolicies )
  {
    $this->certificatePolicies = $certificatePolicies;
    return $this;
  }

  /**
   * @return string
   */
  public function getSubjectKeyIdentifier()
  {
    return $this->subjectKeyIdentifier;
  }

  /**
   * @param string $subjectKeyIdentifier
   * @return $this
   */
  public function setSubjectKeyIdentifier( $subjectKeyIdentifier )
  {
    $this->subjectKeyIdentifier = $subjectKeyIdentifier;
    return $this;
  }

  /**
   * @return string
   */
  public function getQcStatements()
  {
    return $this->qcStatements;
  }

  /**
   * @param string $qcStatements
   * @return $this
   */
  public function setQcStatements( $qcStatements )
  {
    $this->qcStatements = $qcStatements;
    return $this;
  }

  /**
   * @return string
   */
  public function getAuthorityKeyIdentifier()
  {
    return $this->authorityKeyIdentifier;
  }

  /**
   * @param string $authorityKeyIdentifier
   * @return $this
   */
  public function setAuthorityKeyIdentifier( $authorityKeyIdentifier )
  {
    $this->authorityKeyIdentifier = $authorityKeyIdentifier;
    return $this;
  }

  /**
   * @return string
   */
  public function getAuthorityInfoAccess()
  {
    return $this->authorityInfoAccess;
  }

  /**
   * @param string $authorityInfoAccess
   * @return $this
   */
  public function setAuthorityInfoAccess( $authorityInfoAccess )
  {
    $this->authorityInfoAccess = $authorityInfoAccess;
    return $this;
  }

  /**
   * @return string
   */
  public function getExtendedKeyUsage()
  {
    return $this->extendedKeyUsage;
  }

  /**
   * @param string $extendedKeyUsage
   * @return $this
   */
  public function setExtendedKeyUsage( $extendedKeyUsage )
  {
    $this->extendedKeyUsage = $extendedKeyUsage;
    return $this;
  }

  /**
   * @return string
   */
  public function getSubjectAltName()
  {
    return $this->subjectAltName;
  }

  /**
   * @param string $subjectAltName
   * @return $this
   */
  public function setSubjectAltName( $subjectAltName )
  {
    $this->subjectAltName = $subjectAltName;
    return $this;
  }
}