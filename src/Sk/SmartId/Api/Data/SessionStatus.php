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

class SessionStatus extends PropertyMapper
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

  /**
   * @return bool
   */
  public function isRunningState()
  {
    return strcasecmp( SessionStatusCode::RUNNING, $this->state ) == 0;
  }
}
