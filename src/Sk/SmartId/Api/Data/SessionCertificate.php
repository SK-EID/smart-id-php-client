<?php
namespace Sk\SmartId\Api\Data;

class SessionCertificate extends AbstractData
{
  /**
   * @var string
   */
  private $value;

  /**
   * @deprecated
   * @var string
   */
  private $assuranceLevel;

  /**
   * @var string
   */
  private $certificateLevel;

  /**
   * @return string
   */
  public function getValue()
  {
    return $this->value;
  }

  /**
   * @param string $value
   * @return $this
   */
  public function setValue( $value )
  {
    $this->value = $value;
    return $this;
  }

  /**
   * @deprecated
   * @return mixed
   */
  public function getAssuranceLevel()
  {
    return $this->assuranceLevel;
  }

  /**
   * @deprecated
   * @param mixed $assuranceLevel
   */
  public function setAssuranceLevel( $assuranceLevel )
  {
    $this->assuranceLevel = $assuranceLevel;
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
}