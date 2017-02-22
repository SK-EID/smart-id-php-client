<?php
namespace Sk\SmartId\Api;

use Sk\SmartId\Api\Data\SessionStatus;
use Sk\SmartId\Api\Data\SessionStatusCode;
use Sk\SmartId\Api\Data\SessionStatusRequest;
use Sk\SmartId\Api\Data\SessionEndResultCode;
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
    while ( $sessionStatus === null || strcasecmp( SessionStatusCode::RUNNING, $sessionStatus->getState() ) == 0 )
    {
      $sessionStatus = $this->pollSessionStatus( $sessionId );
      if ( $sessionStatus && strcasecmp( SessionStatusCode::COMPLETE, $sessionStatus->getState() ) == 0 )
      {
        break;
      }

      // @TODO Set value either in seconds or nanoseconds (for nanoseconds `time_nanosleep` func. is required )
      sleep( 1 );
    }
    return $sessionStatus;
  }

  /**
   * @param string $sessionId
   * @return SessionStatus
   */
  private function pollSessionStatus( $sessionId )
  {
    $request = $this->createSessionStatusRequest( $sessionId );
    return $this->connector->getSessionStatus( $request );
  }

  /**
   * @param string $sessionId
   * @return SessionStatusRequest
   */
  private function createSessionStatusRequest( $sessionId )
  {
    $request = new SessionStatusRequest( $sessionId );
    // @TODO Take into account option to set request long poll timeout in milliseconds
    return $request;
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
}