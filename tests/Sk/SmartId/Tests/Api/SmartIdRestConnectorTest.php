<?php

namespace Sk\SmartId\Tests\Api;

use Exception;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\Api\SmartIdRestConnector;
use Sk\SmartId\Client;

class SmartIdRestConnectorTest extends TestCase
{

  /**
   * @test
   * @throws Exception
   */
  public function postRequest_toOnlineEchoServiceHttpBin_userAgentContainsLibraryVersion()
  {
    $connector = new SmartIdRestConnector( "https://httpbin.org/anything?" );

    $request = array();
    $request['paramKey'] = 'paramValue';

    $sentRequest = $connector->postRequest("https://httpbin.org/anything?", $request, 'Sk\SmartId\Tests\Api\HttpBinDummyResponse');

    $this->assertRequestContainsSmartIdPhpClientLibraryVersion($sentRequest);
    $this->assertRequestContainsSmartIdPhpClientLibraryName($sentRequest);
    $this->assertRequestContainsPhpAsProgrammingLanguage($sentRequest);
    $this->assertRequestUserAgentMatchesFormat($sentRequest);
  }

  /**
   * @test
   * @throws Exception
   */
  public function getRequest_toOnlineEchoServiceHttpBin_userAgentContainsLibraryVersion()
  {
    $connector = new SmartIdRestConnector( "https://httpbin.org/anything?" );

    $request = array();
    $request['paramKey'] = 'paramValue';

    $sentGetRequest = $connector->getRequest("https://httpbin.org/anything?", $request, 'Sk\SmartId\Tests\Api\HttpBinDummyResponse');

    self::assertStringContainsString(Client::VERSION, $sentGetRequest->getUserAgentHeader());
    self::assertStringContainsString("smart-id-php-client/", $sentGetRequest->getUserAgentHeader());
    self::assertStringContainsString("(PHP/", $sentGetRequest->getUserAgentHeader());

    self::assertMatchesRegularExpression("%smart-id-php-client/[0-9]\.[0-9]\..+ \(PHP/[0-9]+\.[0-9]+\..+\)%", $sentGetRequest->getUserAgentHeader());
  }

  /**
   * @param $sentRequest
   */
  public function assertRequestContainsSmartIdPhpClientLibraryVersion($sentRequest): void
  {
    self::assertStringContainsString(Client::VERSION, $sentRequest->getUserAgentHeader());
  }

  /**
   * @param $sentRequest
   */
  public function assertRequestContainsSmartIdPhpClientLibraryName($sentRequest): void
  {
    self::assertStringContainsString("smart-id-php-client/", $sentRequest->getUserAgentHeader());
  }

  /**
   * @param $sentRequest
   */
  public function assertRequestContainsPhpAsProgrammingLanguage($sentRequest): void
  {
    self::assertStringContainsString("(PHP/", $sentRequest->getUserAgentHeader());
  }

  /**
   * @param $sentRequest
   */
  public function assertRequestUserAgentMatchesFormat($sentRequest): void
  {
    self::assertMatchesRegularExpression("%smart-id-php-client/[0-9]\.[0-9]\..+ \(PHP/[0-9]+\.[0-9]+\..+\)%", $sentRequest->getUserAgentHeader());
  }

}
