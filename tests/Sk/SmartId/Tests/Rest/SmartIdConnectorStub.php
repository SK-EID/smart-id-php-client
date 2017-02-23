<?php
namespace Sk\SmartId\Tests\Rest;

use Sk\SmartId\Api\Data\AuthenticationSessionRequest;
use Sk\SmartId\Api\Data\AuthenticationSessionResponse;
use Sk\SmartId\Api\Data\NationalIdentity;
use Sk\SmartId\Api\Data\SessionStatus;
use Sk\SmartId\Api\Data\SessionStatusRequest;
use Sk\SmartId\Api\SmartIdConnector;
use Sk\SmartId\Exception\SessionNotFoundException;

class SmartIdConnectorStub implements SmartIdConnector
{
  /**
   * @var string
   */
  public $sessionIdUsed;

  /**
   * @var SessionStatusRequest
   */
  public $requestUsed;

  /**
   * @var SessionStatus[]
   */
  public $responses;

  /**
   * @var int
   */
  public $responseNumber = 0;

  /**
   * @param string $documentNumber
   * @param AuthenticationSessionRequest $request
   * @return AuthenticationSessionResponse
   */
  function authenticate( $documentNumber, AuthenticationSessionRequest $request )
  {
    return null;
  }

  /**
   * @param NationalIdentity $identity
   * @param AuthenticationSessionRequest $request
   * @return AuthenticationSessionResponse
   */
  function authenticateWithIdentity( NationalIdentity $identity, AuthenticationSessionRequest $request )
  {
    return null;
  }

  /**
   * @param SessionStatusRequest $request
   * @throws SessionNotFoundException
   * @return SessionStatus
   */
  function getSessionStatus( SessionStatusRequest $request )
  {
    $this->sessionIdUsed = $request->getSessionId();
    $this->requestUsed = $request;
    return $this->responses[ $this->responseNumber++ ];
  }
}