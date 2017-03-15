<?php
namespace Sk\SmartId\Tests\Api;

use Sk\SmartId\Api\Data\HashType;
use Sk\SmartId\Api\Data\SignableData;
use Sk\SmartId\Api\Data\SignableDataGenerator;
use Sk\SmartId\Api\Data\SmartIdAuthenticationResponse;
use Sk\SmartId\Tests\Setup;

class SmartIdClientIntegrationTest extends Setup
{
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

    $this->assertAuthenticationResultCreated( $authenticationResult );
  }

  /**
   * @test
   */
  public function authenticate_withNationalIdentityNumberAndCountryCode()
  {
    $dataToSign = SignableDataGenerator::generate( HashType::SHA512 );
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

    $this->assertAuthenticationResultCreated( $authenticationResult );
  }

  /**
   * @param SmartIdAuthenticationResponse $authenticationResponse
   */
  private function assertAuthenticationResultCreated( SmartIdAuthenticationResponse $authenticationResponse )
  {
    $this->assertNotNull( $authenticationResponse );
    $this->assertNotEmpty( $authenticationResponse->getEndResult() );
    $this->assertNotEmpty( $authenticationResponse->getValueInBase64() );
    $this->assertNotNull( $authenticationResponse->getCertificate() );
    $this->assertNotNull( $authenticationResponse->getCertificateInstance() );
    $this->assertNotNull( $authenticationResponse->getCertificateLevel() );
  }
}