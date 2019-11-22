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
