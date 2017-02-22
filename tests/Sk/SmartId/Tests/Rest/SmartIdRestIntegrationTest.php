<?php
namespace Sk\SmartId\Tests\Rest;

use Exception;
use Sk\SmartId\Api\Data\SignableData;
use Sk\SmartId\Api\Data\SmartIdAuthenticationResult;
use Sk\SmartId\Tests\Setup;

class SmartIdRestIntegrationTest extends Setup
{
  /**
   * @test
   * @throws Exception
   */
  public function authenticate()
  {
    $dataToSign = new SignableData( $GLOBALS['data_to_sign'] );

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
    $this->assertNotNull( $authenticationResult->getCertificateLevel() );
  }
}