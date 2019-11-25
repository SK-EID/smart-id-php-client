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

use Sk\SmartId\Api\Data\SessionEndResultCode;
use Sk\SmartId\Api\Data\SessionStatus;
use Sk\SmartId\Api\Data\SessionStatusCode;
use Sk\SmartId\Api\Data\SessionStatusRequest;
use Sk\SmartId\Exception\DocumentUnusableException;
use Sk\SmartId\Exception\InterruptedException;
use Sk\SmartId\Exception\SessionTimeoutException;
use Sk\SmartId\Exception\TechnicalErrorException;
use Sk\SmartId\Exception\UserRefusedException;

class SessionStatusPoller
{
  /**
   * @var SmartIdConnector
   */
  private $connector;

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
   * @throws TechnicalErrorException
   * @return SessionStatus|null
   */
  public function fetchFinalSessionStatus( $sessionId )
  {
    try
    {
      $sessionStatus = $this->pollForFinalSessionStatus( $sessionId );
      $this->validateResult( $sessionStatus );
      return $sessionStatus;
    }
    catch ( InterruptedException $e )
    {
      throw new TechnicalErrorException( 'Failed to poll session status: ' . $e->getMessage() );
    }
  }

  /**
   * @param string $sessionId
   * @return SessionStatus|null
   */
  private function pollForFinalSessionStatus( $sessionId )
  {
    /** @var SessionStatus $sessionStatus */
    $sessionStatus = null;
    $sessionStatusFetcher = $this->createSessionStatusFetcher( $sessionId );
    while ( $sessionStatus === null || ( $sessionStatus && $sessionStatus->isRunningState() ) )
    {
      $sessionStatus = $sessionStatusFetcher->getSessionStatus();
      if ( $sessionStatus && !$sessionStatus->isRunningState() )
      {
        break;
      }
      $microseconds = $this->convertMsToMicros( $this->pollingSleepTimeoutMs );
      usleep( $microseconds );
    }
    return $sessionStatus;
  }

  /**
   * @param SessionStatus $sessionStatus
   * @throws TechnicalErrorException
   * @throws UserRefusedException
   * @throws SessionTimeoutException
   * @throws DocumentUnusableException
   */
  private function validateResult( SessionStatus $sessionStatus )
  {
    $result = $sessionStatus->getResult();
    if ( $result === null )
    {
      throw new TechnicalErrorException( 'Result is missing in the session status response' );
    }

    $endResult = $result->getEndResult();
    if ( strcasecmp( $endResult, SessionEndResultCode::USER_REFUSED ) == 0 )
    {
      throw new UserRefusedException();
    }
    else if ( strcasecmp( $endResult, SessionEndResultCode::TIMEOUT ) == 0 )
    {
      throw new SessionTimeoutException();
    }
    else if ( strcasecmp( $endResult, SessionEndResultCode::DOCUMENT_UNUSABLE ) == 0 )
    {
      throw new DocumentUnusableException();
    }
    else if ( strcasecmp( $endResult, SessionEndResultCode::OK ) != 0 )
    {
      throw new TechnicalErrorException( 'Session status end result is \'' . $endResult . '\'' );
    }
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
   * @param int $milliseconds
   * @return int
   */
  private function convertMsToMicros( $milliseconds )
  {
    $conversionResult = $milliseconds * pow( 10, 3 );
    return $conversionResult > PHP_INT_MAX ? PHP_INT_MAX : $conversionResult;
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

  /**
   * @param string $sessionId
   * @return SessionStatusFetcher
   */
  private function createSessionStatusFetcher( $sessionId )
  {
    $sessionStatusFetcherBuilder = new SessionStatusFetcherBuilder( $this->connector );
    return $sessionStatusFetcherBuilder
        ->withSessionId( $sessionId )
        ->withSessionStatusResponseSocketTimeoutMs( $this->sessionStatusResponseSocketTimeoutMs )
        ->withNetworkInterface($this->networkInterface)
        ->build();
  }

  public function withNetworkInterface( $networkInterface )
    {
        $this->networkInterface = $networkInterface;
        return $this;
    }
}
