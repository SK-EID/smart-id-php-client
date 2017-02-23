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
  private $sessionStatusResponseSocketOpenTimeoutMs;

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
  public function getSessionStatusResponseSocketOpenTimeoutMs()
  {
    return $this->sessionStatusResponseSocketOpenTimeoutMs;
  }

  /**
   * @param int $sessionStatusResponseSocketOpenTimeoutMs
   */
  public function setSessionStatusResponseSocketOpenTimeoutMs( $sessionStatusResponseSocketOpenTimeoutMs )
  {
    $this->sessionStatusResponseSocketOpenTimeoutMs = $sessionStatusResponseSocketOpenTimeoutMs;
  }

  /**
   * @return bool
   */
  public function isSessionStatusResponseSocketOpenTimeoutSet()
  {
    return isset( $this->sessionStatusResponseSocketOpenTimeoutMs ) && $this->sessionStatusResponseSocketOpenTimeoutMs > 0;
  }
}