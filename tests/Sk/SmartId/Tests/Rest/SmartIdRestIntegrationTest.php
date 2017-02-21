<?php
namespace Sk\SmartId\Tests\Rest;

use Exception;
use Sk\SmartId\Api\Data\SignableData;
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

    $this->assertNotEmpty( $authenticationResult->getSessionID() );
  }
}