<?php
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
  public function withSessionId( $sessionId )
  {
    $this->sessionId = $sessionId;
    return $this;
  }

  /**
   * @param SignableData $dataToSign
   * @return $this
   */
  public function withSignableData( SignableData $dataToSign )
  {
    $this->dataToSign = $dataToSign;
    return $this;
  }

  /**
   * @param AuthenticationHash $authenticationHash
   * @return $this
   */
  public function withAuthenticationHash( AuthenticationHash $authenticationHash )
  {
    $this->authenticationHash = $authenticationHash;
    return $this;
  }

  /**
   * @param int $sessionStatusResponseSocketTimeoutMs
   * @throws TechnicalErrorException
   * @return $this
   */
  public function withSessionStatusResponseSocketTimeoutMs( $sessionStatusResponseSocketTimeoutMs )
  {
    if ( $sessionStatusResponseSocketTimeoutMs < 0 )
    {
      throw new TechnicalErrorException( 'Timeout can not be negative' );
    }
    $this->sessionStatusResponseSocketTimeoutMs = $sessionStatusResponseSocketTimeoutMs;
    return $this;
  }

  /**
   * @return Data\SmartIdAuthenticationResponse
   */
  public function getAuthenticationResponse()
  {
    return $this->build()
        ->getAuthenticationResponse();
  }

  /**
   * @return SessionStatusFetcher
   */
  public function build()
  {
    $sessionStatusFetcher = new SessionStatusFetcher( $this->connector );
    $sessionStatusFetcher->setSessionId( $this->sessionId )
        ->setSessionStatusResponseSocketTimeoutMs( $this->sessionStatusResponseSocketTimeoutMs );
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