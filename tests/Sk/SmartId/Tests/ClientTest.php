<?php
namespace Sk\SmartId\Tests;

use Sk\SmartId\Api\ApiType;

class ClientTest extends Setup
{
  /**
   * @test
   */
  public function testApi()
  {
    $AuthenticationService = $this->client->api( ApiType::AUTHENTICATION );
    $this->assertInstanceOf( 'Sk\SmartId\Api\Authentication', $AuthenticationService,
        'AuthenticationService is not of Api\Authentication type!' );

    $AuthenticationService = $this->client->authentication();
    $this->assertInstanceOf( 'Sk\SmartId\Api\Authentication', $AuthenticationService,
        'AuthenticationService is not of Api\Authentication type!' );
  }
}
