<?php
namespace Sk\SmartId\Api\Data;

class AuthenticationCertificateSubject extends AbstractData
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
  public function getC()
  {
    return $this->C;
  }

  /**
   * @param string $C
   * @return $this
   */
  public function setC( $C )
  {
    $this->C = $C;
    return $this;
  }

  /**
   * @return string
   */
  public function getO()
  {
    return $this->O;
  }

  /**
   * @param string $O
   * @return $this
   */
  public function setO( $O )
  {
    $this->O = $O;
    return $this;
  }

  /**
   * @return string
   */
  public function getOU()
  {
    return $this->OU;
  }

  /**
   * @param string $OU
   * @return $this
   */
  public function setOU( $OU )
  {
    $this->OU = $OU;
    return $this;
  }

  /**
   * @return string
   */
  public function getCN()
  {
    return $this->CN;
  }

  /**
   * @param string $CN
   * @return $this
   */
  public function setCN( $CN )
  {
    $this->CN = $CN;
    return $this;
  }

  /**
   * @return string
   */
  public function getSN()
  {
    return $this->SN;
  }

  /**
   * @param string $SN
   * @return $this
   */
  public function setSN( $SN )
  {
    $this->SN = $SN;
    return $this;
  }

  /**
   * @return string
   */
  public function getGN()
  {
    return $this->GN;
  }

  /**
   * @param string $GN
   * @return $this
   */
  public function setGN( $GN )
  {
    $this->GN = $GN;
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
}