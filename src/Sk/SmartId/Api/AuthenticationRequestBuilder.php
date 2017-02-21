<?php
namespace Sk\SmartId\Api;

use Sk\SmartId\Api\Data\AuthenticationSessionRequest;
use Sk\SmartId\Api\Data\AuthenticationSessionResponse;
use Sk\SmartId\Api\Data\NationalIdentity;
use Sk\SmartId\Api\Data\SignableData;
use Sk\SmartId\Api\Data\SignableHash;

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
   * @var SignableHash
   */
  private $hashToSign;

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
   */
  public function __construct( SmartIdConnector $connector )
  {
    parent::__construct( $connector );
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
   * @param SignableHash $hashToSign
   * @return $this
   */
  public function withHash( SignableHash $hashToSign )
  {
    $this->hashToSign = $hashToSign;
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
   * @return AuthenticationSessionResponse
   */
  public function authenticate()
  {
    $this->validateParameters();
    $request  = $this->createAuthenticationSessionRequest();
    $response = $this->getAuthenticationResponse( $request );
    return $response;
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
    if ( isset( $this->hashToSign ) )
    {
      return $this->hashToSign->getHashType();
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
    if ( isset( $this->hashToSign ) )
    {
      return $this->hashToSign->getHashInBase64();
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
}