<?php
namespace Sk\SmartId\Api;

use Sk\SmartId\Api\Data\AuthenticationSessionRequest;
use Sk\SmartId\Api\Data\AuthenticationSessionResponse;
use Sk\SmartId\Api\Data\NationalIdentity;

interface SmartIdConnector
{
  /**
   * @param string $documentNumber
   * @param AuthenticationSessionRequest $request
   * @return AuthenticationSessionResponse
   */
  function authenticate( $documentNumber, AuthenticationSessionRequest $request );

  /**
   * @param NationalIdentity $identity
   * @param AuthenticationSessionRequest $request
   * @return AuthenticationSessionResponse
   */
  function authenticateWithIdentity( NationalIdentity $identity, AuthenticationSessionRequest $request );
}