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

use Sk\SmartId\Api\Data\AuthenticationHash;
use Sk\SmartId\Api\Data\SignableData;
use Sk\SmartId\Exception\TechnicalErrorException;

class SessionStatusFetcherBuilder
{
  /**
   * @var string
   */
  private $sessionId;

  /**
   * @var SmartIdConnector
   */
  private $connector;

  /**
   * @var SignableData
   */
  private $dataToSign;

  /**
   * @var AuthenticationHash
   */
  private $authenticationHash;

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
   * @param SmartIdConnector $connector
   */
  public function __construct( SmartIdConnector $connector )
  {
    $this->connector = $connector;
  }

  /**
   * @param string $sessionId
   * @return $this
   */
  public function withSessionId(string $sessionId ): SessionStatusFetcherBuilder
  {
    $this->sessionId = $sessionId;
    return $this;
  }

  /**
   * @param SignableData $dataToSign
   * @return $this
   */
  public function withSignableData( SignableData $dataToSign ): SessionStatusFetcherBuilder
  {
    $this->dataToSign = $dataToSign;
    return $this;
  }

  /**
   * @param AuthenticationHash $authenticationHash
   * @return $this
   */
  public function withAuthenticationHash( AuthenticationHash $authenticationHash ): SessionStatusFetcherBuilder
  {
    $this->authenticationHash = $authenticationHash;
    return $this;
  }

  /**
   * @param int|null $sessionStatusResponseSocketTimeoutMs
   * @return $this
   */
  public function withSessionStatusResponseSocketTimeoutMs( ?int $sessionStatusResponseSocketTimeoutMs): SessionStatusFetcherBuilder
  {
    if ( $sessionStatusResponseSocketTimeoutMs < 0 )
    {
      throw new TechnicalErrorException( 'Timeout can not be negative' );
    }
    $this->sessionStatusResponseSocketTimeoutMs = $sessionStatusResponseSocketTimeoutMs;
    return $this;
  }

  /**
   * @param string|null $networkInterface
   * @return $this
   */
  public function withNetworkInterface(?string $networkInterface ): SessionStatusFetcherBuilder
  {
    $this->networkInterface = $networkInterface;
    return $this;
  }

  /**
   * @return Data\SmartIdAuthenticationResponse
   */
  public function getAuthenticationResponse(): Data\SmartIdAuthenticationResponse
  {
    return $this->build()
        ->getAuthenticationResponse();
  }

  /**
   * @return SessionStatusFetcher
   */
  public function build(): SessionStatusFetcher
  {
    $sessionStatusFetcher = new SessionStatusFetcher( $this->connector );
    $sessionStatusFetcher->setSessionId( $this->sessionId )
        ->setSessionStatusResponseSocketTimeoutMs( $this->sessionStatusResponseSocketTimeoutMs )
        ->setNetworkInterface( $this->networkInterface );
    if ( $this->dataToSign )
    {
      $sessionStatusFetcher->setDataToSign( $this->dataToSign );
    }
    if ( $this->authenticationHash )
    {
      $sessionStatusFetcher->setAuthenticationHash( $this->authenticationHash );
    }
    return $sessionStatusFetcher;
  }
}
