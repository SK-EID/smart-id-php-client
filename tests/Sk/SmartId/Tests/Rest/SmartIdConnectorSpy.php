<?php
namespace Sk\SmartId\Tests\Rest;

use Sk\SmartId\Api\Data\AuthenticationSessionRequest;
use Sk\SmartId\Api\Data\AuthenticationSessionResponse;
use Sk\SmartId\Api\Data\NationalIdentity;
use Sk\SmartId\Api\SmartIdConnector;

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
   * @var NationalIdentity
   */
  public $identityUsed;

  /**
   * @param string $documentNumber
   * @param AuthenticationSessionRequest $request
   * @return AuthenticationSessionResponse
   */
  function authenticate( $documentNumber, AuthenticationSessionRequest $request )
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
  function authenticateWithIdentity( NationalIdentity $identity, AuthenticationSessionRequest $request )
  {
    $this->identityUsed = $identity;
    $this->authenticationSessionRequestUsed = $request;
    return $this->authenticationSessionResponseToRespond;
  }
}