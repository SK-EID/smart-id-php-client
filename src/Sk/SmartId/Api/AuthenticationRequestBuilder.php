<?php
namespace Sk\SmartId\Api;

use Sk\SmartId\Api\Data\AuthenticationSessionRequest;
use Sk\SmartId\Api\Data\AuthenticationSessionResponse;
use Sk\SmartId\Api\Data\NationalIdentity;
use Sk\SmartId\Api\Data\SessionStatus;
use Sk\SmartId\Api\Data\SignableData;
use Sk\SmartId\Api\Data\SmartIdAuthenticationResult;
use Sk\SmartId\Exception\InvalidParametersException;
use Sk\SmartId\Exception\TechnicalErrorException;

class AuthenticationRequestBuilder extends SmartIdRequestBuilder
{
  /**
   * @var string
   */
  private $countryCode;

  /**
   * @var string
   */
  private $nationalIdentityNumber;

  /**
   * @var NationalIdentity
   */
  private $nationalIdentity;

  /**
   * @var string
   */
  private $documentNumber;

  /**
   * @var string
   */
  private $certificateLevel;

  /**
   * @var SignableData
   */
  private $dataToSign;

  /**
   * @var string
   */
  private $hashType;

  /**
   * @var string
   */
  private $hashInBase64;

  /**
   * @param SmartIdConnector $connector
   * @param SessionStatusPoller $sessionStatusPoller
   */
  public function __construct( SmartIdConnector $connector, SessionStatusPoller $sessionStatusPoller )
  {
    parent::__construct( $connector, $sessionStatusPoller );
  }

  /**
   * @param string $documentNumber
   * @return $this
   */
  public function withDocumentNumber( $documentNumber )
  {
    $this->documentNumber = $documentNumber;
    return $this;
  }

  /**
   * @param NationalIdentity $nationalIdentity
   * @return $this
   */
  public function withNationalIdentity( NationalIdentity $nationalIdentity )
  {
    $this->nationalIdentity = $nationalIdentity;
    return $this;
  }

  /**
   * @param string $countryCode
   * @return $this
   */
  public function withCountryCode( $countryCode )
  {
    $this->countryCode = $countryCode;
    return $this;
  }

  /**
   * @param string $nationalIdentityNumber
   * @return $this
   */
  public function withNationalIdentityNumber( $nationalIdentityNumber )
  {
    $this->nationalIdentityNumber = $nationalIdentityNumber;
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
   * @param string $hashType
   * @return $this
   */
  public function withHashType( $hashType )
  {
    $this->hashType = $hashType;
    return $this;
  }

  /**
   * @param string $hashInBase64
   * @return $this
   */
  public function withHashInBase64( $hashInBase64 )
  {
    $this->hashInBase64 = $hashInBase64;
    return $this;
  }

  /**
   * @param string $certificateLevel
   * @return $this
   */
  public function withCertificateLevel( $certificateLevel )
  {
    $this->certificateLevel = $certificateLevel;
    return $this;
  }

  /**
   * @param string $relyingPartyUUID
   * @return $this
   */
  public function withRelyingPartyUUID( $relyingPartyUUID )
  {
    parent::withRelyingPartyUUID( $relyingPartyUUID );
    return $this;
  }

  /**
   * @param string $relyingPartyName
   * @return $this
   */
  public function withRelyingPartyName( $relyingPartyName )
  {
    parent::withRelyingPartyName( $relyingPartyName );
    return $this;
  }

  /**
   * @return SmartIdAuthenticationResult
   */
  public function authenticate()
  {
    $this->validateParameters();
    $request = $this->createAuthenticationSessionRequest();
    $response = $this->getAuthenticationResponse( $request );
    $sessionStatus = $this->getSessionStatusPoller()->fetchFinalSessionStatus( $response->getSessionID() );
    $this->validateResponse( $sessionStatus );
    $authenticationResult = $this->createSmartIdAuthenticationResult( $sessionStatus );
    return $authenticationResult;
  }

  /**
   * @return AuthenticationSessionRequest
   */
  private function createAuthenticationSessionRequest()
  {
    $request = new AuthenticationSessionRequest();
    $request
        ->setRelyingPartyUUID( $this->getRelyingPartyUUID() )
        ->setRelyingPartyName( $this->getRelyingPartyName() )
        ->setCertificateLevel( $this->certificateLevel )
        ->setHashType( $this->getHashTypeString() )
        ->setHash( $this->getHashInBase64() );
    return $request;
  }

  /**
   * @return string
   */
  private function getHashTypeString()
  {
    if ( isset( $this->hashType ) )
    {
      return $this->hashType;
    }
    return $this->dataToSign->getHashType();
  }

  /**
   * @return string
   */
  private function getHashInBase64()
  {
    if ( strlen( $this->hashInBase64 ) )
    {
      return $this->hashInBase64;
    }
    return $this->dataToSign->calculateHashInBase64();
  }

  /**
   * @param AuthenticationSessionRequest $request
   * @return AuthenticationSessionResponse
   */
  private function getAuthenticationResponse( AuthenticationSessionRequest $request )
  {
    if ( strlen( $this->documentNumber ) )
    {
      return $this->getConnector()->authenticate( $this->documentNumber, $request );
    }
    else
    {
      $identity = $this->getNationalIdentity();
      return $this->getConnector()->authenticateWithIdentity( $identity, $request );
    }
  }

  /**
   * @return NationalIdentity
   */
  private function getNationalIdentity()
  {
    if ( isset( $this->nationalIdentity ) )
    {
      return $this->nationalIdentity;
    }
    return new NationalIdentity( $this->countryCode, $this->nationalIdentityNumber );
  }

  /**
   * @throws InvalidParametersException
   */
  protected function validateParameters()
  {
    parent::validateParameters();
    if ( !isset( $this->documentNumber ) && !$this->hasNationalIdentity() )
    {
      throw new InvalidParametersException( 'Either document number or national identity must be set' );
    }
    if ( !isset( $this->certificateLevel ) )
    {
      throw new InvalidParametersException( 'Certificate level must be set' );
    }
    if ( !$this->isHashSet() && !$this->isSignableDataSet() )
    {
      throw new InvalidParametersException( 'Signable data or hash with hash type must be set' );
    }
  }

  /**
   * @return bool
   */
  private function hasNationalIdentity()
  {
    return isset( $this->nationalIdentity )
        || ( strlen( $this->countryCode ) && strlen( $this->nationalIdentityNumber ) );
  }

  /**
   * @return bool
   */
  private function isHashSet()
  {
    return strlen( $this->hashType ) && strlen( $this->hashInBase64 );
  }

  /**
   * @return bool
   */
  private function isSignableDataSet()
  {
    return isset( $this->dataToSign );
  }

  /**
   * @param SessionStatus $sessionStatus
   * @throws TechnicalErrorException
   */
  private function validateResponse( SessionStatus $sessionStatus )
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
   * @return SmartIdAuthenticationResult
   */
  private function createSmartIdAuthenticationResult( SessionStatus $sessionStatus )
  {
    $sessionResult = $sessionStatus->getResult();
    $sessionSignature = $sessionStatus->getSignature();
    $sessionCertificate = $sessionStatus->getCert();
    $authenticationResult = new SmartIdAuthenticationResult();
    $authenticationResult->setDocumentNumber( $sessionResult->getDocumentNumber() );
    $authenticationResult->setEndResult( $sessionResult->getEndResult() );
    $authenticationResult->setValueInBase64( $sessionSignature->getValue() );
    $authenticationResult->setAlgorithmName( $sessionSignature->getAlgorithm() );
    $authenticationResult->setCertificate( $sessionCertificate->getValue() );
    $authenticationResult->setCertificateLevel( $sessionCertificate->getCertificateLevel() );
    return $authenticationResult;
  }
}