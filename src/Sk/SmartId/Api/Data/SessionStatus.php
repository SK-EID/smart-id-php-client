<?php
namespace Sk\SmartId\Api\Data;

class SessionStatus extends AbstractData
{
  /**
   * @var string
   */
  private $state;

  /**
   * @var SessionResult
   */
  private $result;

  /**
   * @var SessionSignature
   */
  private $signature;

  /**
   * @var SessionCertificate
   */
  private $cert;

  /**
   * @return string
   */
  public function getState()
  {
    return $this->state;
  }

  /**
   * @param string $state
   * @return $this
   */
  public function setState( $state )
  {
    $this->state = $state;
    return $this;
  }

  /**
   * @return SessionResult
   */
  public function getResult()
  {
    return $this->result;
  }

  /**
   * @param SessionResult $result
   * @return $this
   */
  public function setResult( SessionResult $result = null )
  {
    $this->result = $result;
    return $this;
  }

  /**
   * @return SessionSignature
   */
  public function getSignature()
  {
    return $this->signature;
  }

  /**
   * @param SessionSignature $signature
   * @return $this
   */
  public function setSignature( SessionSignature $signature = null )
  {
    $this->signature = $signature;
    return $this;
  }

  /**
   * @return SessionCertificate
   */
  public function getCert()
  {
    return $this->cert;
  }

  /**
   * @param SessionCertificate $cert
   * @return $this
   */
  public function setCert( SessionCertificate $cert = null )
  {
    $this->cert = $cert;
    return $this;
  }
}