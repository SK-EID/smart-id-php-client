<?php
/*-
 * #%L
 * Smart ID sample PHP client
 * %%
 * Copyright (C) 2018 - 2019 SK ID Solutions AS
 * %%
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 * #L%
 */
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
   * @param string|null $sessionId
   */
  public function __construct(?string $sessionId )
  {
    $this->sessionId = $sessionId;
  }

  /**
   * @return string
   */
  public function getSessionId(): ?string
  {
    return $this->sessionId;
  }

  /**
   * @return int
   */
  public function getSessionStatusResponseSocketTimeoutMs(): int
  {
    return $this->sessionStatusResponseSocketTimeoutMs;
  }

  /**
   * @param int|null $sessionStatusResponseSocketTimeoutMs
   * @return $this
   */
  public function setSessionStatusResponseSocketTimeoutMs(?int $sessionStatusResponseSocketTimeoutMs ): SessionStatusRequest
  {
    $this->sessionStatusResponseSocketTimeoutMs = $sessionStatusResponseSocketTimeoutMs;
    return $this;
  }

  /**
   * @return bool
   */
  public function isSessionStatusResponseSocketTimeoutSet(): bool
  {
    return isset( $this->sessionStatusResponseSocketTimeoutMs ) && $this->sessionStatusResponseSocketTimeoutMs > 0;
  }

  /**
   * @param string|null $networkInterface
   * @return $this
   */
  public function setNetworkInterface(?string $networkInterface ): SessionStatusRequest
  {
    $this->networkInterface = $networkInterface;
    return $this;
  }

  /**
   * @return array
   */
  public function toArray(): array
  {
    $requiredArray = array();

    if ( $this->isSessionStatusResponseSocketTimeoutSet() )
    {
      $requiredArray[ 'timeoutMs' ] = $this->sessionStatusResponseSocketTimeoutMs;
    }

    if ( isset( $this->networkInterface ) )
    {
      $requiredArray[ 'networkInterface' ] = $this->networkInterface;
    }

    return $requiredArray;
  }
}
