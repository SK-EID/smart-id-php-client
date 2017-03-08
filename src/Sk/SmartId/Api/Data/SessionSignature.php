<?php
namespace Sk\SmartId\Api\Data;

class SessionSignature extends PropertyMapper
{
  /**
   * @var string
   */
  private $algorithm;

  /**
   * @var string
   */
  private $value;

  /**
   * @return string
   */
  public function getAlgorithm()
  {
    return $this->algorithm;
  }

  /**
   * @param string $algorithm
   * @return $this
   */
  public function setAlgorithm( $algorithm )
  {
    $this->algorithm = $algorithm;
    return $this;
  }

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
}