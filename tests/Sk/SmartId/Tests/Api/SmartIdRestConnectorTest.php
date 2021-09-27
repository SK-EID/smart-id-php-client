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

    $this->assertHeaderContainsSmartIdPhpClientLibraryVersion($sentRequest->getUserAgentHeader());
    $this->assertHeaderContainsSmartIdPhpClientLibraryName($sentRequest->getUserAgentHeader());
    $this->assertHeaderContainsPhpAsProgrammingLanguage($sentRequest->getUserAgentHeader());
    $this->assertHeaderMatchesFormat($sentRequest->getUserAgentHeader());
  }

  /**
   * @test
   * @throws Exception
   */
  public function headerFormatsMatchValidation()
  {
    $this->assertHeaderMatchesFormat("smart-id-php-client/2.2 (PHP/7.3.27)");
    $this->assertHeaderMatchesFormat("smart-id-php-client/2.2.SNAPSHOT (PHP/8.0)");
    $this->assertHeaderMatchesFormat("smart-id-php-client/2.2.SNAPSHOT (PHP/8.1.3.1)");
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

    $this->assertHeaderContainsSmartIdPhpClientLibraryVersion($sentGetRequest->getUserAgentHeader());
    $this->assertHeaderContainsSmartIdPhpClientLibraryName($sentGetRequest->getUserAgentHeader());
    $this->assertHeaderContainsPhpAsProgrammingLanguage($sentGetRequest->getUserAgentHeader());
    $this->assertHeaderMatchesFormat($sentGetRequest->getUserAgentHeader());
  }

  /**
   * @param $userAgentHeaderValue
   */
  public function assertHeaderContainsSmartIdPhpClientLibraryVersion($userAgentHeaderValue): void
  {
    self::assertStringContainsString(Client::VERSION, $userAgentHeaderValue);
  }

  /**
   * @param $userAgentHeaderValue
   */
  public function assertHeaderContainsSmartIdPhpClientLibraryName($userAgentHeaderValue): void
  {
    self::assertStringContainsString("smart-id-php-client/", $userAgentHeaderValue);
  }

  /**
   * @param $userAgentHeaderValue
   */
  public function assertHeaderContainsPhpAsProgrammingLanguage($userAgentHeaderValue): void
  {
    self::assertStringContainsString("(PHP/", $userAgentHeaderValue);
  }

  /**
   * @param $sentRequest
   */
  public function assertHeaderMatchesFormat($userAgentHeaderValue): void
  {
    self::assertMatchesRegularExpression("%smart-id-php-client/[0-9]\.[0-9].* \(PHP/[0-9]+\.[0-9]+.*\)%", $userAgentHeaderValue);
  }

}
