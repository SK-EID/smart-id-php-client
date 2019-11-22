<?php
namespace Sk\SmartId\Tests;

use PHPUnit\Framework\TestCase;
use Sk\SmartId\Client;
use Sk\SmartId\Tests\Api\DummyData;
use Sk\SmartId\Util\Curl;

class Setup extends TestCase
{
  const RESOURCES = __DIR__ . '/../../../resources';
  
  /**
   * @var Client
   */
  protected $client;

  protected function setUp()
  {
    $this->client = new Client();
    $this->client
        ->setRelyingPartyUUID( DummyData::DEMO_RELYING_PARTY_UUID )
        ->setRelyingPartyName( DummyData::DEMO_RELYING_PARTY_NAME )
        ->setHostUrl( DummyData::DEMO_HOST_URL );
  }
}
