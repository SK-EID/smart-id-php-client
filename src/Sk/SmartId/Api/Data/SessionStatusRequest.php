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
   */
  public function setSessionStatusResponseSocketTimeoutMs( $sessionStatusResponseSocketTimeoutMs )
  {
    $this->sessionStatusResponseSocketTimeoutMs = $sessionStatusResponseSocketTimeoutMs;
  }

  /**
   * @return bool
   */
  public function isSessionStatusResponseSocketTimeoutSet()
  {
    return isset( $this->sessionStatusResponseSocketTimeoutMs ) &&
        $this->sessionStatusResponseSocketTimeoutMs > 0;
  }
}