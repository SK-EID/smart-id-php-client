<?php
namespace Sk\SmartId\Api\Data;

class NationalIdentity
{
  /**
   * @var string
   */
  private $countryCode;

  /**
   * @var string
   */
  private $nationalIdentityNumber;

  /**
   * @return string
   */
  public function getCountryCode()
  {
    return $this->countryCode;
  }

  /**
   * @param string $countryCode
   * @return $this
   */
  public function setCountryCode( $countryCode )
  {
    $this->countryCode = $countryCode;
    return $this;
  }

  /**
   * @return string
   */
  public function getNationalIdentityNumber()
  {
    return $this->nationalIdentityNumber;
  }

  /**
   * @param string $nationalIdentityNumber
   * @return $this
   */
  public function setNationalIdentityNumber( $nationalIdentityNumber )
  {
    $this->nationalIdentityNumber = $nationalIdentityNumber;
    return $this;
  }
}