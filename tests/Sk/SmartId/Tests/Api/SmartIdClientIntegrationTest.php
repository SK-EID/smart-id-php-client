<?php
namespace Sk\SmartId\Tests\Api;

use Sk\SmartId\Api\Data\HashType;
use Sk\SmartId\Api\Data\SignableDataGenerator;
use Sk\SmartId\Api\Data\SmartIdAuthenticationResult;
use Sk\SmartId\Tests\Setup;

class SmartIdClientIntegrationTest extends Setup
{
  /**
   * @test
   */
  public function authenticate()
  {
    $dataToSign = SignableDataGenerator::generate( HashType::SHA512 );
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
   * @param SmartIdAuthenticationResult $authenticationResult
   */
  private function assertAuthenticationResultCreated( SmartIdAuthenticationResult $authenticationResult )
  {
    $this->assertNotNull( $authenticationResult );
    $this->assertNotEmpty( $authenticationResult->getEndResult() );
    $this->assertNotEmpty( $authenticationResult->getValueInBase64() );
    $this->assertNotNull( $authenticationResult->getCertificate() );
    $this->assertNotNull( $authenticationResult->getCertificateInstance() );
    $this->assertNotNull( $authenticationResult->getCertificateLevel() );
  }
}