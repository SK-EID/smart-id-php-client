<?php
namespace Sk\SmartId\Tests\Api;

use Sk\SmartId\Api\AuthenticationResponseValidator;
use Sk\SmartId\Api\Data\AuthenticationHash;
use Sk\SmartId\Api\Data\AuthenticationIdentity;
use Sk\SmartId\Api\Data\SmartIdAuthenticationResponse;
use Sk\SmartId\Api\Data\SmartIdAuthenticationResult;
use Sk\SmartId\Tests\Setup;

class SmartIdClientIntegrationTest extends Setup
{
  /**
   * @after
   */
  public function waitForMobileAppToFinish()
  {
    sleep( 10 );
  }

  /**
   * @test
   */
  public function authenticate_withDocumentNumber()
  {
    $authenticationHash = AuthenticationHash::generate();
    $this->assertNotEmpty( $authenticationHash->calculateVerificationCode() );

    $authenticationResponse = $this->client->authentication()
        ->createAuthentication()
        ->withRelyingPartyUUID( $GLOBALS[ 'relying_party_uuid' ] )
        ->withRelyingPartyName( $GLOBALS[ 'relying_party_name' ] )
        ->withDocumentNumber( $GLOBALS[ 'document_number' ] )
        ->withAuthenticationHash( $authenticationHash )
        ->withCertificateLevel( $GLOBALS[ 'certificate_level' ] )
        ->authenticate();

    $this->assertAuthenticationResponseCreated( $authenticationResponse, $authenticationHash->getDataToSign() );

    $authenticationResponseValidator = new AuthenticationResponseValidator( self::RESOURCES );
    $authenticationResult = $authenticationResponseValidator->validate( $authenticationResponse );
    $this->assertAuthenticationResultValid( $authenticationResult );
  }

  /**
   * @test
   */
  public function authenticate_withNationalIdentityNumberAndCountryCode()
  {
    $authenticationHash = AuthenticationHash::generate();
    $this->assertNotEmpty( $authenticationHash->calculateVerificationCode() );

    $authenticationResponse = $this->client->authentication()
        ->createAuthentication()
        ->withRelyingPartyUUID( $GLOBALS[ 'relying_party_uuid' ] )
        ->withRelyingPartyName( $GLOBALS[ 'relying_party_name' ] )
        ->withNationalIdentityNumber( $GLOBALS[ 'national_identity_number' ] )
        ->withCountryCode( $GLOBALS[ 'country_code' ] )
        ->withAuthenticationHash( $authenticationHash )
        ->withCertificateLevel( $GLOBALS[ 'certificate_level' ] )
        ->authenticate();

    $this->assertAuthenticationResponseCreated( $authenticationResponse, $authenticationHash->getDataToSign() );

    $authenticationResponseValidator = new AuthenticationResponseValidator( self::RESOURCES );
    $authenticationResult = $authenticationResponseValidator->validate( $authenticationResponse );
    $this->assertAuthenticationResultValid( $authenticationResult );
  }

  /**
   * @test
   */
  public function startAuthenticationAndReturnSessionId_withNationalIdentityNumberAndCountryCode()
  {
    $authenticationHash = AuthenticationHash::generate();
    $this->assertNotEmpty( $authenticationHash->calculateVerificationCode() );

    $sessionId = $this->client->authentication()
        ->createAuthentication()
        ->withRelyingPartyUUID( $GLOBALS[ 'relying_party_uuid' ] )
        ->withRelyingPartyName( $GLOBALS[ 'relying_party_name' ] )
        ->withNationalIdentityNumber( $GLOBALS[ 'national_identity_number' ] )
        ->withCountryCode( $GLOBALS[ 'country_code' ] )
        ->withAuthenticationHash( $authenticationHash )
        ->withCertificateLevel( $GLOBALS[ 'certificate_level' ] )
        ->startAuthenticationAndReturnSessionId();

    $this->assertNotEmpty( $sessionId );
  }

  /**
   * @test
   */
  public function getAuthenticationResponse_withSessionId_isRunning()
  {
    $authenticationHash = AuthenticationHash::generate();
    $this->assertNotEmpty( $authenticationHash->calculateVerificationCode() );

    $sessionId = $this->client->authentication()
        ->createAuthentication()
        ->withRelyingPartyUUID( $GLOBALS[ 'relying_party_uuid' ] )
        ->withRelyingPartyName( $GLOBALS[ 'relying_party_name' ] )
        ->withNationalIdentityNumber( $GLOBALS[ 'national_identity_number' ] )
        ->withCountryCode( $GLOBALS[ 'country_code' ] )
        ->withAuthenticationHash( $authenticationHash )
        ->withCertificateLevel( $GLOBALS[ 'certificate_level' ] )
        ->withDisplayText( 'DO NOT ENTER CODE' )
        ->startAuthenticationAndReturnSessionId();

    $this->assertNotEmpty( $sessionId );

    $authenticationResponse = $this->client->authentication()
        ->setSessionStatusResponseSocketTimeoutMs( 1000 )
        ->createSessionStatusFetcher()
        ->withSessionId( $sessionId )
        ->withAuthenticationHash( $authenticationHash )
        ->getAuthenticationResponse();

    $this->assertNotNull( $authenticationResponse );
    $this->assertTrue( $authenticationResponse->isRunningState() );
  }

  /**
   * @test
   */
  public function getAuthenticationResponse_withSessionId_isComplete()
  {
    $authenticationHash = AuthenticationHash::generate();
    $this->assertNotEmpty( $authenticationHash->calculateVerificationCode() );

    $sessionId = $this->client->authentication()
        ->createAuthentication()
        ->withRelyingPartyUUID( $GLOBALS[ 'relying_party_uuid' ] )
        ->withRelyingPartyName( $GLOBALS[ 'relying_party_name' ] )
        ->withNationalIdentityNumber( $GLOBALS[ 'national_identity_number' ] )
        ->withCountryCode( $GLOBALS[ 'country_code' ] )
        ->withAuthenticationHash( $authenticationHash )
        ->withCertificateLevel( $GLOBALS[ 'certificate_level' ] )
        ->startAuthenticationAndReturnSessionId();

    $this->assertNotEmpty( $sessionId );

    // Polling logic exposed
    $authenticationResponse = null;

    for ( $i = 0; $i <= 10; $i++ )
    {
      $authenticationResponse = $this->client->authentication()
          ->createSessionStatusFetcher()
          ->withSessionId( $sessionId )
          ->withAuthenticationHash( $authenticationHash )
          ->withSessionStatusResponseSocketTimeoutMs( 10000 )
          ->getAuthenticationResponse();

      $this->assertNotNull( $authenticationResponse );

      if ( !$authenticationResponse->isRunningState() )
      {
        break;
      }
      sleep( 5 );
    }

    $this->assertNotNull( $authenticationResponse );
    $this->assertFalse( $authenticationResponse->isRunningState() );

    $this->assertAuthenticationResponseCreated( $authenticationResponse, $authenticationHash->getDataToSign() );

    $authenticationResponseValidator = new AuthenticationResponseValidator( self::RESOURCES );
    $authenticationResult = $authenticationResponseValidator->validate( $authenticationResponse );
    $this->assertAuthenticationResultValid( $authenticationResult );
  }

  /**
   * @param SmartIdAuthenticationResponse $authenticationResponse
   * @param string $dataToSign
   */
  private function assertAuthenticationResponseCreated( SmartIdAuthenticationResponse $authenticationResponse,
                                                        $dataToSign )
  {
    $this->assertNotNull( $authenticationResponse );
    $this->assertNotEmpty( $authenticationResponse->getEndResult() );
    $this->assertEquals( $dataToSign, $authenticationResponse->getSignedData() );
    $this->assertNotEmpty( $authenticationResponse->getValueInBase64() );
    $this->assertNotNull( $authenticationResponse->getCertificate() );
    $this->assertNotNull( $authenticationResponse->getCertificateInstance() );
    $this->assertNotNull( $authenticationResponse->getCertificateLevel() );
  }

  /**
   * @param SmartIdAuthenticationResult $authenticationResult
   */
  private function assertAuthenticationResultValid( SmartIdAuthenticationResult $authenticationResult )
  {
    $this->assertTrue( $authenticationResult->isValid() );
    $this->assertTrue( empty( $authenticationResult->getErrors() ) );
    $this->assertAuthenticationIdentityValid( $authenticationResult->getAuthenticationIdentity() );
  }

  /**
   * @param AuthenticationIdentity $authenticationIdentity
   */
  private function assertAuthenticationIdentityValid( AuthenticationIdentity $authenticationIdentity )
  {
    $this->assertNotEmpty( $authenticationIdentity->getGivenName() );
    $this->assertNotEmpty( $authenticationIdentity->getSurName() );
    $this->assertNotEmpty( $authenticationIdentity->getIdentityCode() );
    $this->assertNotEmpty( $authenticationIdentity->getCountry() );
  }
}