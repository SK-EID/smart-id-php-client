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
   * @var array
   */
  private $ignoredProperties;

  /**
   * @var string
   */
  private $interactionFlowUsed;

  /**
   * @return string
   */
  public function getState(): string
  {
    return $this->state;
  }

  /**
   * @param string $state
   * @return $this
   */
  public function setState(string $state ): SessionStatus
  {
    $this->state = $state;
    return $this;
  }

  /**
   * @return SessionResult
   */
  public function getResult(): ?SessionResult
  {
    return $this->result;
  }

    /**
     * @param SessionResult|null $result
     * @return $this
     */
  public function setResult( SessionResult $result = null ): SessionStatus
  {
    $this->result = $result;
    return $this;
  }

  /**
   * @return SessionSignature
   */
  public function getSignature(): ?SessionSignature
  {
    return $this->signature;
  }

    /**
     * @param SessionSignature|null $signature
     * @return $this
     */
  public function setSignature( SessionSignature $signature = null ): SessionStatus
  {
    $this->signature = $signature;
    return $this;
  }

  /**
   * @return SessionCertificate
   */
  public function getCert(): ?SessionCertificate
  {
    return $this->cert;
  }

    /**
     * @param SessionCertificate|null $cert
     * @return $this
     */
  public function setCert( SessionCertificate $cert = null ): SessionStatus
  {
    $this->cert = $cert;
    return $this;
  }

    /**
     * @return array
     */
    public function getIgnoredProperties(): ?array
    {
        return $this->ignoredProperties;
    }

    /**
     * @param array $ignoredProperties
     */
    public function setIgnoredProperties(array $ignoredProperties)
    {
        $this->ignoredProperties = $ignoredProperties;
    }

    /**
     * @return string
     */
    public function getInteractionFlowUsed(): string
    {
        return $this->interactionFlowUsed;
    }

    /**
     * @param string $interactionFlowUsed
     * @return SessionStatus
     */
    public function setInteractionFlowUsed(string $interactionFlowUsed): SessionStatus
    {
        $this->interactionFlowUsed = $interactionFlowUsed;
        return $this;
    }



  /**
   * @return bool
   */
  public function isRunningState(): bool
  {
    return strcasecmp( SessionStatusCode::RUNNING, $this->state ) == 0;
  }
}
