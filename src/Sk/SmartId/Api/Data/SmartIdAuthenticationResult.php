<?php
namespace Sk\SmartId\Api\Data;

use Sk\SmartId\Exception\TechnicalErrorException;

class SmartIdAuthenticationResult extends AbstractData
{
  /**
   * @var string
   */
  private $endResult;

  /**
   * @var string
   */
  private $valueInBase64;

  /**
   * @var string
   */
  private $algorithmName;

  /**
   * @var string
   */
  private $documentNumber;

  /**
   * @var array
   */
  private $certificate;

  /**
   * @var string
   */
  private $certificateLevel;

  /**
   * @return string
   */
  public function getEndResult()
  {
    return $this->endResult;
  }

  /**
   * @param string $endResult
   * @return $this
   */
  public function setEndResult( $endResult )
  {
    $this->endResult = $endResult;
    return $this;
  }

  /**
   * @return string
   */
  public function getValueInBase64()
  {
    return $this->valueInBase64;
  }

  /**
   * @param string $valueInBase64
   * @return $this
   */
  public function setValueInBase64( $valueInBase64 )
  {
    $this->valueInBase64 = $valueInBase64;
    return $this;
  }

  /**
   * @return string
   */
  public function getAlgorithmName()
  {
    return $this->algorithmName;
  }

  /**
   * @param string $algorithmName
   * @return $this
   */
  public function setAlgorithmName( $algorithmName )
  {
    $this->algorithmName = $algorithmName;
    return $this;
  }

  /**
   * @return string
   */
  public function getDocumentNumber()
  {
    return $this->documentNumber;
  }

  /**
   * @param string $documentNumber
   * @return $this
   */
  public function setDocumentNumber( $documentNumber )
  {
    $this->documentNumber = $documentNumber;
    return $this;
  }

  /**
   * @return string
   */
  public function getCertificate()
  {
    return $this->certificate;
  }

  /**
   * @return array
   */
  public function getParsedCertificate()
  {
    return CertificateParser::parseX509Certificate( $this->certificate );
  }

  /**
   * @param string $certificate
   * @return $this
   */
  public function setCertificate( $certificate )
  {
    $this->certificate = $certificate;
    return $this;
  }

  /**
   * @return string
   */
  public function getCertificateLevel()
  {
    return $this->certificateLevel;
  }

  /**
   * @param string $certificateLevel
   * @return $this
   */
  public function setCertificateLevel( $certificateLevel )
  {
    $this->certificateLevel = $certificateLevel;
    return $this;
  }

  /**
   * @throws TechnicalErrorException
   * @return string
   */
  public function getValue()
  {
    if ( base64_decode( $this->valueInBase64, true ) === false )
    {
      throw new TechnicalErrorException( 'Failed to parse signature value in base64. Probably incorrectly 
                encoded base64 string: ' . $this->valueInBase64 );
    }
    return base64_decode( $this->valueInBase64, true );
  }
}