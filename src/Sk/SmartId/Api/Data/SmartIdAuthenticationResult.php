<?php
namespace Sk\SmartId\Api\Data;

class SmartIdAuthenticationResult
{
  /**
   * @var AuthenticationIdentity
   */
  private $authenticationIdentity;

  /**
   * @var boolean
   */
  private $valid;

  /**
   * @var array
   */
  private $errors;

  public function __construct()
  {
    $this->valid = true;
    $this->errors = array();
  }

  /**
   * @return AuthenticationIdentity
   */
  public function getAuthenticationIdentity()
  {
    return $this->authenticationIdentity;
  }

  /**
   * @param AuthenticationIdentity $authenticationIdentity
   * @return $this
   */
  public function setAuthenticationIdentity( AuthenticationIdentity $authenticationIdentity )
  {
    $this->authenticationIdentity = $authenticationIdentity;
    return $this;
  }

  /**
   * @return bool
   */
  public function isValid()
  {
    return $this->valid;
  }

  /**
   * @param boolean $valid
   * @return $this
   */
  public function setValid( $valid )
  {
    $this->valid = $valid;
    return $this;
  }

  /**
   * @param string $error
   * @return $this
   */
  public function addError( $error )
  {
    $this->errors[] = $error;
    return $this;
  }

  /**
   * @return array
   */
  public function getErrors()
  {
    return $this->errors;
  }
}