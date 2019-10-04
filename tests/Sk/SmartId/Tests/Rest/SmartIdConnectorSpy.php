<?php
namespace Sk\SmartId\Tests\Rest;

use Sk\SmartId\Api\Data\AuthenticationSessionRequest;
use Sk\SmartId\Api\Data\AuthenticationSessionResponse;
use Sk\SmartId\Api\Data\NationalIdentity;
use Sk\SmartId\Api\Data\SessionStatus;
use Sk\SmartId\Api\Data\SessionStatusRequest;
use Sk\SmartId\Api\SmartIdConnector;
use Sk\SmartId\Exception\SessionNotFoundException;

class SmartIdConnectorSpy implements SmartIdConnector
{
  /**
   * @var AuthenticationSessionResponse
   */
  public $authenticationSessionResponseToRespond;

  /**
   * @var string
   */
  public $documentNumberUsed;

  /**
   * @var AuthenticationSessionRequest
   */
  public $authenticationSessionRequestUsed;

  /**
   * @var SessionStatusRequest
   */
  public $sessionStatusRequestUsed;

  /**
   * @var NationalIdentity
   */
  public $identityUsed;

  /**
   * @var string
   */
  public $sessionIdUsed;

  /**
   * @var SessionStatus
   */
  public $sessionStatusToRespond;

  /**
   * @param string $documentNumber
   * @param AuthenticationSessionRequest $request
   * @return AuthenticationSessionResponse
   */
  public function authenticate( $documentNumber, AuthenticationSessionRequest $request )
  {
    $this->documentNumberUsed = $documentNumber;
    $this->authenticationSessionRequestUsed = $request;
    return $this->authenticationSessionResponseToRespond;
  }

  /**
   * @param NationalIdentity $identity
   * @param AuthenticationSessionRequest $request
   * @return AuthenticationSessionResponse
   */
  public function authenticateWithIdentity( NationalIdentity $identity, AuthenticationSessionRequest $request )
  {
    $this->identityUsed = $identity;
    $this->authenticationSessionRequestUsed = $request;
    return $this->authenticationSessionResponseToRespond;
  }

  /**
   * @param SessionStatusRequest $request
   * @throws SessionNotFoundException
   * @return SessionStatus
   */
  public function getSessionStatus( SessionStatusRequest $request )
  {
    $this->sessionIdUsed = $request->getSessionId();
    $this->sessionStatusRequestUsed = $request;
    return $this->sessionStatusToRespond;
  }
}