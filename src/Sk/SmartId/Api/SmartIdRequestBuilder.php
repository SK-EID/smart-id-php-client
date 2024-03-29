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
namespace Sk\SmartId\Api;

use Sk\SmartId\Exception\InvalidParametersException;

abstract class SmartIdRequestBuilder
{
  /**
   * @var SmartIdConnector
   */
  private $connector;

  /**
   * @var SessionStatusPoller
   */
  private $sessionStatusPoller;

  /**
   * @var string
   */
  private $relyingPartyUUID;

  /**
   * @var string
   */
  private $relyingPartyName;

  /**
   * @var string
   */
  private $networkInterface;

  /**
   * @var array
   */
  private $allowedInteractionsOrder;

  /**
   * @param SmartIdConnector $connector
   * @param SessionStatusPoller $sessionStatusPoller
   */
  public function __construct( SmartIdConnector $connector, SessionStatusPoller $sessionStatusPoller )
  {
    $this->connector = $connector;
    $this->sessionStatusPoller = $sessionStatusPoller;
  }

  /**
   * @param string $relyingPartyUUID
   * @return $this
   */
  public function withRelyingPartyUUID(string $relyingPartyUUID ): SmartIdRequestBuilder
  {
    $this->relyingPartyUUID = $relyingPartyUUID;
    return $this;
  }

  /**
   * @param string $relyingPartyName
   * @return $this
   */
  public function withRelyingPartyName(string $relyingPartyName ): SmartIdRequestBuilder
  {
    $this->relyingPartyName = $relyingPartyName;
    return $this;
  }

  /**
   * @param string $networkInterface
   * @return $this
   */
  public function withNetworkInterface(string $networkInterface ): SmartIdRequestBuilder
  {
    $this->networkInterface = $networkInterface;
    return $this;
  }

  /**
   * @return SmartIdConnector
   */
  public function getConnector(): SmartIdConnector
  {
    return $this->connector;
  }

  /**
   * @return SessionStatusPoller
   */
  public function getSessionStatusPoller(): SessionStatusPoller
  {
    return $this->sessionStatusPoller->withNetworkInterface($this->getNetworkInterface());
  }

  /**
   * @return string
   */
  public function getRelyingPartyUUID(): string
  {
    return $this->relyingPartyUUID;
  }

  /**
   * @return string
   */
  public function getRelyingPartyName(): string
  {
    return $this->relyingPartyName;
  }

  /**
   * @return string
   */
  public function getNetworkInterface(): ?string
  {
    return $this->networkInterface;
  }

    /**
     * @return array
     */
    public function getAllowedInteractionsOrder(): array
    {
        return $this->allowedInteractionsOrder;
    }

  /**
   * @throws InvalidParametersException
   */
  protected function validateParameters()
  {
    if ( !isset( $this->relyingPartyUUID ) )
    {
      throw new InvalidParametersException( 'Relying Party UUID parameter must be set' );
    }

    if ( !isset( $this->relyingPartyName ) )
    {
      throw new InvalidParametersException( 'Relying Party Name parameter must be set' );
    }
  }
}
