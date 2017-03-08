<?php
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
  public function getName()
  {
    return $this->name;
  }

  /**
   * @param string $name
   * @return $this
   */
  public function setName( $name )
  {
    $this->name = $name;
    return $this;
  }

  /**
   * @return AuthenticationCertificateSubject
   */
  public function getSubject()
  {
    return $this->subject;
  }

  /**
   * @param AuthenticationCertificateSubject $subject
   * @return $this
   */
  public function setSubject( AuthenticationCertificateSubject $subject = null )
  {
    $this->subject = $subject;
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
   * @param string $hash
   * @return $this
   */
  public function setHash( $hash )
  {
    $this->hash = $hash;
    return $this;
  }

  /**
   * @return AuthenticationCertificateIssuer
   */
  public function getIssuer()
  {
    return $this->issuer;
  }

  /**
   * @param AuthenticationCertificateIssuer $issuer
   * @return $this
   */
  public function setIssuer( AuthenticationCertificateIssuer $issuer = null )
  {
    $this->issuer = $issuer;
    return $this;
  }

  /**
   * @return int
   */
  public function getVersion()
  {
    return $this->version;
  }

  /**
   * @param int $version
   * @return $this
   */
  public function setVersion( $version )
  {
    $this->version = $version;
    return $this;
  }

  /**
   * @return string
   */
  public function getSerialNumber()
  {
    return $this->serialNumber;
  }

  /**
   * @param string $serialNumber
   * @return $this
   */
  public function setSerialNumber( $serialNumber )
  {
    $this->serialNumber = $serialNumber;
    return $this;
  }

  /**
   * @return string
   */
  public function getValidFrom()
  {
    return $this->validFrom;
  }

  /**
   * @param string $validFrom
   * @return $this
   */
  public function setValidFrom( $validFrom )
  {
    $this->validFrom = $validFrom;
    return $this;
  }

  /**
   * @return string
   */
  public function getValidTo()
  {
    return $this->validTo;
  }

  /**
   * @param string $validTo
   * @return $this
   */
  public function setValidTo( $validTo )
  {
    $this->validTo = $validTo;
    return $this;
  }

  /**
   * @return int
   */
  public function getValidFromTimeT()
  {
    return $this->validFromTimeT;
  }

  /**
   * @param int $validFromTimeT
   * @return $this
   */
  public function setValidFromTimeT( $validFromTimeT )
  {
    $this->validFromTimeT = $validFromTimeT;
    return $this;
  }

  /**
   * @return int
   */
  public function getValidToTimeT()
  {
    return $this->validToTimeT;
  }

  /**
   * @param int $validToTimeT
   * @return $this
   */
  public function setValidToTimeT( $validToTimeT )
  {
    $this->validToTimeT = $validToTimeT;
    return $this;
  }

  /**
   * @return string
   */
  public function getSignatureTypeSN()
  {
    return $this->signatureTypeSN;
  }

  /**
   * @param string $signatureTypeSN
   * @return $this
   */
  public function setSignatureTypeSN( $signatureTypeSN )
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
  public function setSignatureTypeLN( $signatureTypeLN )
  {
    $this->signatureTypeLN = $signatureTypeLN;
    return $this;
  }

  /**
   * @return int
   */
  public function getSignatureTypeNID()
  {
    return $this->signatureTypeNID;
  }

  /**
   * @param int $signatureTypeNID
   * @return $this
   */
  public function setSignatureTypeNID( $signatureTypeNID )
  {
    $this->signatureTypeNID = $signatureTypeNID;
    return $this;
  }

  /**
   * @return array
   */
  public function getPurposes()
  {
    return $this->purposes;
  }

  /**
   * @param array $purposes
   * @return $this
   */
  public function setPurposes( array $purposes = array() )
  {
    $this->purposes = $purposes;
    return $this;
  }

  /**
   * @return AuthenticationCertificateExtensions
   */
  public function getExtensions()
  {
    return $this->extensions;
  }

  /**
   * @param AuthenticationCertificateExtensions $extensions
   * @return $this
   */
  public function setExtensions( AuthenticationCertificateExtensions $extensions = null )
  {
    $this->extensions = $extensions;
    return $this;
  }
}