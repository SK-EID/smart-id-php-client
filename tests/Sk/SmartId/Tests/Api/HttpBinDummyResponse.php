<?php

namespace Sk\SmartId\Tests\Api;

/**
 * To record the response that is echoed back from the echo service httpbin.org
 */
class HttpBinDummyResponse
{

  /**
   * @var string
   */
  private $userAgentHeader;

  /**
   * @param array $json
   */
  public function __construct( array $json )
  {
    if (isset($json['headers']) && isset($json['headers']['User-Agent'])) {
      $this->userAgentHeader = $json['headers']['User-Agent'];
    }
  }

  /**
   * @return string
   */
  public function getUserAgentHeader(): string
  {
    return $this->userAgentHeader;
  }

}