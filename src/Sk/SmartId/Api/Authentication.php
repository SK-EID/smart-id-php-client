<?php
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
  private $sessionStatusResponseSocketOpenTimeoutMs;

  /**
   * @return AuthenticationRequestBuilder
   */
  public function createAuthentication()
  {
    $connector = new SmartIdRestConnector( $this->client->getHostUrl() );
    $sessionStatusPoller = $this->createSessionStatusPoller( $connector );
    $builder = new AuthenticationRequestBuilder( $connector, $sessionStatusPoller );
    $this->populateBuilderFields( $builder );

    return $builder;
  }

  /**
   * @param SmartIdRequestBuilder $builder
   */
  private function populateBuilderFields( SmartIdRequestBuilder $builder )
  {
    $builder
        ->withRelyingPartyUUID( $this->client->getRelyingPartyUUID() )
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
    $sessionStatusPoller->setSessionStatusResponseSocketOpenTimeoutMs( $this->sessionStatusResponseSocketOpenTimeoutMs );
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
    $conversionResult = $pollingSleepTimeoutMs * pow( 10, 6 );
    $this->pollingSleepTimeoutMs = ( $conversionResult > PHP_INT_MAX ) ? PHP_INT_MAX : $conversionResult;
    return $this;
  }

  /**
   * @param int $sessionStatusResponseSocketOpenTimeoutMs
   * @throws TechnicalErrorException
   * @return $this
   */
  public function setSessionStatusResponseSocketOpenTimeoutMs( $sessionStatusResponseSocketOpenTimeoutMs )
  {
    if ( $sessionStatusResponseSocketOpenTimeoutMs < 0 )
    {
      throw new TechnicalErrorException( 'Timeout can not be negative' );
    }
    $this->sessionStatusResponseSocketOpenTimeoutMs = $sessionStatusResponseSocketOpenTimeoutMs;
    return $this;
  }
}