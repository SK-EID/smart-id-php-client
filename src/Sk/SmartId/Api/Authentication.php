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

use Sk\SmartId\Exception\TechnicalErrorException;

class Authentication extends AbstractApi
{
  /**
   * In milliseconds
   * @var int
   */
  private $pollingSleepTimeoutMs = 1000;

  /**
   * In milliseconds
   * @var int
   */
  private $sessionStatusResponseSocketTimeoutMs;

  /**
   * @return AuthenticationRequestBuilder
   */
  public function createAuthentication()
  {
    $connector = new SmartIdRestConnector( $this->client->getHostUrl() );
    $connector->setPublicSslKeys($this->client->getPublicSslKeys());
    $sessionStatusPoller = $this->createSessionStatusPoller( $connector );
    $builder = new AuthenticationRequestBuilder( $connector, $sessionStatusPoller );
    $this->populateBuilderFields( $builder );

    return $builder;
  }

  /**
   * @return SessionStatusFetcherBuilder
   */
  public function createSessionStatusFetcher()
  {
    $connector = new SmartIdRestConnector( $this->client->getHostUrl() );
    $connector->setPublicSslKeys($this->client->getPublicSslKeys());
    $builder = new SessionStatusFetcherBuilder( $connector );
    return $builder->withSessionStatusResponseSocketTimeoutMs( $this->sessionStatusResponseSocketTimeoutMs );
  }

  /**
   * @param SmartIdRequestBuilder $builder
   */
  private function populateBuilderFields( SmartIdRequestBuilder $builder )
  {
    $builder->withRelyingPartyUUID( $this->client->getRelyingPartyUUID() )
        ->withRelyingPartyName( $this->client->getRelyingPartyName() );
  }

  /**
   * @param SmartIdRestConnector $connector
   * @return SessionStatusPoller
   */
  private function createSessionStatusPoller( SmartIdRestConnector $connector )
  {
    $sessionStatusPoller = new SessionStatusPoller( $connector );
    $sessionStatusPoller->setPollingSleepTimeoutMs( $this->pollingSleepTimeoutMs );
    $sessionStatusPoller->setSessionStatusResponseSocketTimeoutMs( $this->sessionStatusResponseSocketTimeoutMs );
    return $sessionStatusPoller;
  }

  /**
   * @param int $pollingSleepTimeoutMs
   * @throws TechnicalErrorException
   * @return $this
   */
  public function setPollingSleepTimeoutMs( $pollingSleepTimeoutMs )
  {
    if ( $pollingSleepTimeoutMs < 0 )
    {
      throw new TechnicalErrorException( 'Timeout can not be negative' );
    }
    $this->pollingSleepTimeoutMs = $pollingSleepTimeoutMs;
    return $this;
  }

  /**
   * @param int $sessionStatusResponseSocketTimeoutMs
   * @throws TechnicalErrorException
   * @return $this
   */
  public function setSessionStatusResponseSocketTimeoutMs( $sessionStatusResponseSocketTimeoutMs )
  {
    if ( $sessionStatusResponseSocketTimeoutMs < 0 )
    {
      throw new TechnicalErrorException( 'Timeout can not be negative' );
    }
    $this->sessionStatusResponseSocketTimeoutMs = $sessionStatusResponseSocketTimeoutMs;
    return $this;
  }
}
