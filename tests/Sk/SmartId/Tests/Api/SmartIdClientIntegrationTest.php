<?php
namespace Sk\SmartId\Tests\Api;

use Sk\SmartId\Api\Data\AuthenticationHash;
use Sk\SmartId\Api\Data\SignableData;
use Sk\SmartId\Api\Data\SmartIdAuthenticationResponse;
use Sk\SmartId\Tests\Setup;

class SmartIdClientIntegrationTest extends Setup
{
  /**
   * @after
   */
  public function waitForMobileAppToFinish()
  {
    sleep(10);
  }

  /**
   * @test
   */
  public function authenticate_withDocumentNumber()
  {
    $dataToSign = new SignableData( $GLOBALS['data_to_sign'] );
    $this->assertNotNull( $dataToSign );

    $authenticationResult = $this->client->authentication()
        ->createAuthentication()
        ->withRelyingPartyUUID( $GLOBALS['relying_party_uuid'] )
        ->withRelyingPartyName( $GLOBALS['relying_party_name'] )
        ->withDocumentNumber( $GLOBALS['document_number'] )
        ->withSignableData( $dataToSign )
        ->withCertificateLevel( $GLOBALS['certificate_level'] )
        ->authenticate();

    $this->assertAuthenticationResponseCreated( $authenticationResult );
  }

  /**
   * @test
   */
  public function authenticate_withNationalIdentityNumberAndCountryCode()
  {
    $dataToSign = AuthenticationHash::generate();
    $this->assertNotNull( $dataToSign );

    $authenticationResult = $this->client->authentication()
        ->createAuthentication()
        ->withRelyingPartyUUID( $GLOBALS['relying_party_uuid'] )
        ->withRelyingPartyName( $GLOBALS['relying_party_name'] )
        ->withNationalIdentityNumber( $GLOBALS['national_identity_number'] )
        ->withCountryCode( $GLOBALS['country_code'] )
        ->withSignableData( $dataToSign )
        ->withCertificateLevel( $GLOBALS['certificate_level'] )
        ->authenticate();

    $this->assertAuthenticationResponseCreated( $authenticationResult );
  }

  /**
   * @param SmartIdAuthenticationResponse $authenticationResponse
   */
  private function assertAuthenticationResponseCreated( SmartIdAuthenticationResponse $authenticationResponse )
  {
    $this->assertNotNull( $authenticationResponse );
    $this->assertNotEmpty( $authenticationResponse->getEndResult() );
    $this->assertNotEmpty( $authenticationResponse->getValueInBase64() );
    $this->assertNotNull( $authenticationResponse->getCertificate() );
    $this->assertNotNull( $authenticationResponse->getCertificateInstance() );
    $this->assertNotNull( $authenticationResponse->getCertificateLevel() );
  }
}