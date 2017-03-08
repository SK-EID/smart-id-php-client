<?php
namespace Sk\SmartId\Api\Data;

class AuthenticationCertificateIssuer extends PropertyMapper
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
  private $UNDEF;

  /**
   * @var string
   */
  private $CN;

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
  public function getUNDEF()
  {
    return $this->UNDEF;
  }

  /**
   * @param string $UNDEF
   * @return $this
   */
  public function setUNDEF( $UNDEF )
  {
    $this->UNDEF = $UNDEF;
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
}