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

use Sk\SmartId\Api\Data\AuthenticationHash;
use Sk\SmartId\Api\Data\SessionEndResultCode;
use Sk\SmartId\Api\Data\SessionStatus;
use Sk\SmartId\Api\Data\SessionStatusCode;
use Sk\SmartId\Api\Data\SessionStatusRequest;
use Sk\SmartId\Api\Data\SignableData;
use Sk\SmartId\Api\Data\SmartIdAuthenticationResponse;
use Sk\SmartId\Exception\DocumentUnusableException;
use Sk\SmartId\Exception\RequiredInteractionNotSupportedByAppException;
use Sk\SmartId\Exception\SessionTimeoutException;
use Sk\SmartId\Exception\TechnicalErrorException;
use Sk\SmartId\Exception\UserRefusedCertChoiceException;
use Sk\SmartId\Exception\UserRefusedConfirmationMessageException;
use Sk\SmartId\Exception\UserRefusedConfirmationMessageWithVcChoiceException;
use Sk\SmartId\Exception\UserRefusedDisplayTextAndPinException;
use Sk\SmartId\Exception\UserRefusedException;
use Sk\SmartId\Exception\UserRefusedVcChoiceException;

class SessionStatusFetcher
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
   * @param string|null $sessionId
   * @return $this
   */
  public function setSessionId(?string $sessionId ): SessionStatusFetcher
  {
    $this->sessionId = $sessionId;
    return $this;
  }

  /**
   * @return string
   */
  public function getSessionId(): string
  {
    return $this->sessionId;
  }

  /**
   * @param SignableData $dataToSign
   * @return $this
   */
  public function setDataToSign( SignableData $dataToSign ): SessionStatusFetcher
  {
    $this->dataToSign = $dataToSign;
    return $this;
  }

  /**
   * @param AuthenticationHash $authenticationHash
   * @return $this
   */
  public function setAuthenticationHash( AuthenticationHash $authenticationHash ): SessionStatusFetcher
  {
    $this->authenticationHash = $authenticationHash;
    return $this;
  }

  /**
   * @param int|null $sessionStatusResponseSocketTimeoutMs
   * @return $this
   */
  public function setSessionStatusResponseSocketTimeoutMs(?int $sessionStatusResponseSocketTimeoutMs ): SessionStatusFetcher
  {
    $this->sessionStatusResponseSocketTimeoutMs = $sessionStatusResponseSocketTimeoutMs;
    return $this;
  }

  /**
   * @param string|null $networkInterface
   * @return $this
   */
  public function setNetworkInterface(?string $networkInterface ): SessionStatusFetcher
  {
    $this->networkInterface = $networkInterface;
    return $this;
  }

  /**
   * @return string
   */
  private function getDataToSign(): string
  {
    if ( isset( $this->authenticationHash ) )
    {
      return $this->authenticationHash->getDataToSign();
    }
    return $this->dataToSign->getDataToSign();
  }

  /**
   * @return SmartIdAuthenticationResponse
   */
  public function getAuthenticationResponse(): SmartIdAuthenticationResponse
  {
    $sessionStatus = $this->fetchSessionStatus();
    if ( $sessionStatus->isRunningState() )
    {
      $authenticationResponse = new SmartIdAuthenticationResponse();
      $authenticationResponse->setState( SessionStatusCode::RUNNING );
    }
    else
    {
      $this->validateSessionStatus( $sessionStatus );
      $authenticationResponse = $this->createSmartIdAuthenticationResponse( $sessionStatus );
    }
    return $authenticationResponse;
  }

  /**
   * @return SessionStatus
   */
  public function getSessionStatus(): SessionStatus
  {
    $request = $this->createSessionStatusRequest( $this->sessionId );
    return $this->connector->getSessionStatus( $request );
  }

  /**
   * @throws TechnicalErrorException
   * @return SessionStatus|null
   */
  private function fetchSessionStatus(): ?SessionStatus
  {
      $sessionStatus = $this->getSessionStatus();
      $this->validateResult( $sessionStatus );
      return $sessionStatus;
  }

  /**
   * @param string|null $sessionId
   * @return SessionStatusRequest
   */
  private function createSessionStatusRequest(?string $sessionId ): SessionStatusRequest
  {
    $request = new SessionStatusRequest( $sessionId );
    $request->setSessionStatusResponseSocketTimeoutMs( $this->sessionStatusResponseSocketTimeoutMs )
        ->setNetworkInterface( $this->networkInterface );
    return $request;
  }

  /**
   * @param SessionStatus $sessionStatus
   * @throws TechnicalErrorException
   */
  private function validateSessionStatus( SessionStatus $sessionStatus )
  {
    if ( $sessionStatus->getSignature() === null )
    {
      throw new TechnicalErrorException( 'Signature was not present in the response' );
    }
    if ( $sessionStatus->getCert() === null )
    {
      throw new TechnicalErrorException( 'Certificate was not present in the response' );
    }
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
    if ( $sessionStatus->isRunningState() )
    {
      return;
    }

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
    else if ( strcasecmp( $endResult, SessionEndResultCode::REQUIRED_INTERACTION_NOT_SUPPORTED_BY_APP) == 0 )
    {
        throw new RequiredInteractionNotSupportedByAppException();
    }
    else if ( strcasecmp( $endResult, SessionEndResultCode::USER_REFUSED_DISPLAYTEXTANDPIN) == 0 )
    {
        throw new UserRefusedDisplayTextAndPinException();
    }
    else if ( strcasecmp( $endResult, SessionEndResultCode::USER_REFUSED_VC_CHOICE) == 0 )
    {
        throw new UserRefusedVcChoiceException();
    }
    else if ( strcasecmp( $endResult, SessionEndResultCode::USER_REFUSED_CONFIRMATIONMESSAGE) == 0 )
    {
        throw new UserRefusedConfirmationMessageException();
    }
    else if ( strcasecmp( $endResult, SessionEndResultCode::USER_REFUSED_CONFIRMATIONMESSAGE_WITH_VC_CHOICE) == 0 )
    {
        throw new UserRefusedConfirmationMessageWithVcChoiceException();
    }
    else if ( strcasecmp( $endResult, SessionEndResultCode::USER_REFUSED_CERT_CHOICE) == 0 )
    {
        throw new UserRefusedCertChoiceException();
    }
    else if ( strcasecmp( $endResult, SessionEndResultCode::OK ) != 0 )
    {
      throw new TechnicalErrorException( 'Session status end result is \'' . $endResult . '\'' );
    }
  }

  /**
   * @param SessionStatus $sessionStatus
   * @return SmartIdAuthenticationResponse
   */
  private function createSmartIdAuthenticationResponse( SessionStatus $sessionStatus ): SmartIdAuthenticationResponse
  {
    $sessionResult = $sessionStatus->getResult();
    $sessionSignature = $sessionStatus->getSignature();
    $sessionCertificate = $sessionStatus->getCert();

    $authenticationResponse = new SmartIdAuthenticationResponse();
    $authenticationResponse->setEndResult( $sessionResult->getEndResult() )
        ->setSignedData( $this->getDataToSign() )
        ->setValueInBase64( $sessionSignature->getValue() )
        ->setAlgorithmName( $sessionSignature->getAlgorithm() )
        ->setCertificate( $sessionCertificate->getValue() )
        ->setCertificateLevel( $sessionCertificate->getCertificateLevel() )
        ->setState( $sessionStatus->getState() );
    return $authenticationResponse;
  }
}
