<?php
namespace Sk\SmartId\Tests;

use PHPUnit\Framework\TestCase;
use Sk\SmartId\Client;

class Setup extends TestCase
{
  /**
   * @var Client
   */
  protected $client;

  protected function setUp()
  {
    $this->client = new Client();
    $this->client->setRelyingPartyUUID( $GLOBALS['relying_party_uuid'] )
        ->setRelyingPartyName( $GLOBALS['relying_party_name'] )
        ->setHostUrl( $GLOBALS['host_url'] );
  }
}