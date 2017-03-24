<?php
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
   * @var string
   */
  private $identityCode;

  /**
   * @var string
   */
  private $country;

  /**
   * @return string
   */
  public function getGivenName()
  {
    return $this->givenName;
  }

  /**
   * @param string $givenName
   * @return $this
   */
  public function setGivenName( $givenName )
  {
    $this->givenName = $givenName;
    return $this;
  }

  /**
   * @return string
   */
  public function getSurName()
  {
    return $this->surName;
  }

  /**
   * @param string $surName
   * @return $this
   */
  public function setSurName( $surName )
  {
    $this->surName = $surName;
    return $this;
  }

  /**
   * @return string
   */
  public function getIdentityCode()
  {
    return $this->identityCode;
  }

  /**
   * @param string $identityCode
   * @return $this
   */
  public function setIdentityCode( $identityCode )
  {
    $this->identityCode = $identityCode;
    return $this;
  }

  /**
   * @return string
   */
  public function getCountry()
  {
    return $this->country;
  }

  /**
   * @param string $country
   * @return $this
   */
  public function setCountry( $country )
  {
    $this->country = $country;
    return $this;
  }
}