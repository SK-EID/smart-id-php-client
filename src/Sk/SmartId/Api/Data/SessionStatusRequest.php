<?php
namespace Sk\SmartId\Api\Data;

class SessionStatusRequest
{
  /**
   * @var string
   */
  private $sessionId;

  /**
   * In milliseconds
   * @var int
   */
  private $sessionStatusResponseSocketTimeoutMs;

  /**
   * @var string
   */
  private $networkInterface;

  /**
   * @param string $sessionId
   */
  public function __construct( $sessionId )
  {
    $this->sessionId = $sessionId;
  }

  /**
   * @return string
   */
  public function getSessionId()
  {
    return $this->sessionId;
  }

  /**
   * @return int
   */
  public function getSessionStatusResponseSocketTimeoutMs()
  {
    return $this->sessionStatusResponseSocketTimeoutMs;
  }

  /**
   * @param int $sessionStatusResponseSocketTimeoutMs
   * @return $this
   */
  public function setSessionStatusResponseSocketTimeoutMs( $sessionStatusResponseSocketTimeoutMs )
  {
    $this->sessionStatusResponseSocketTimeoutMs = $sessionStatusResponseSocketTimeoutMs;
    return $this;
  }

  /**
   * @return bool
   */
  public function isSessionStatusResponseSocketTimeoutSet()
  {
    return isset( $this->sessionStatusResponseSocketTimeoutMs ) && $this->sessionStatusResponseSocketTimeoutMs > 0;
  }

  /**
   * @param string $networkInterface
   * @return $this
   */
  public function setNetworkInterface( $networkInterface )
  {
    $this->networkInterface = $networkInterface;
    return $this;
  }

  /**
   * @return array
   */
  public function toArray()
  {
    $requiredArray = array();

    if ( $this->isSessionStatusResponseSocketTimeoutSet() )
    {
      $params[ 'timeoutMs' ] = $this->sessionStatusResponseSocketTimeoutMs;
    }

    if ( isset( $this->networkInterface ) )
    {
      $requiredArray[ 'networkInterface' ] = $this->networkInterface;
    }

    return $requiredArray;
  }
}
