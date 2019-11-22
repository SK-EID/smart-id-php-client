<?php
/*-
 * #%L
 * Smart ID sample PHP client
 * %%
 * Copyright (C) 2018 - 2019 SK ID Solutions AS
 * %%
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 * #L%
 */
namespace Sk\SmartId;

use InvalidArgumentException;
use Sk\SmartId\Api\AbstractApi;
use Sk\SmartId\Api\ApiType;
use Sk\SmartId\Api\Authentication;

class Client
{
  const
          DEMO_SID_PUBLIC_KEY = "sha256//QLZIaH7Qx9Rjq3gyznQuNsvwMQb7maC5L4SLu/z5qNU=",
          RP_API_PUBLIC_KEY_VALID_FROM_2016_12_20_TO_2020_01_19 = "sha256//R8b8SIj92sylUdok0DqfxJJN0yW2O3epE0B+5vpo2eM=",
          RP_API_PUBLIC_KEY_VALID_FROM_2019_11_01_TO_2021_11_05 = "sha256//l2uvq6ftLN4LZ+8Un+71J2vH1BT9wTbtrE5+Fj3Vc5g=",
          VERSION = '5.0';

  /**
   * @var array
   */
  private $apis = array();

  /**
   * @var string
   */
  private $relyingPartyUUID;

  /**
   * @var string
   */
  private $relyingPartyName;

  /**
   * @var string
   */
  private $hostUrl;

    /**
     * @var string
     */
  private $sslKeys;

  /**
   * @param string $apiName
   * @throws InvalidArgumentException
   * @return AbstractApi
   */
  public function api( $apiName )
  {
    switch ( $apiName )
    {
      case ApiType::AUTHENTICATION:
      {
        return $this->authentication();
      }

      default:
      {
        throw new InvalidArgumentException( 'No such api at present time!' );
      }
    }
  }

  /**
   * @return Authentication
   */
  public function authentication()
  {
    if ( !isset( $this->apis['authentication'] ) )
    {
      $this->apis['authentication'] = new Authentication( $this );
    }

    return $this->apis['authentication'];
  }

  /**
   * @param string $relyingPartyUUID
   * @return $this
   */
  public function setRelyingPartyUUID( $relyingPartyUUID )
  {
    $this->relyingPartyUUID = $relyingPartyUUID;

    return $this;
  }

  /**
   * @return string
   */
  public function getRelyingPartyUUID()
  {
    return $this->relyingPartyUUID;
  }

  /**
   * @param string $relyingPartyName
   * @return $this
   */
  public function setRelyingPartyName( $relyingPartyName )
  {
    $this->relyingPartyName = $relyingPartyName;

    return $this;
  }

  /**
   * @return string
   */
  public function getRelyingPartyName()
  {
    return $this->relyingPartyName;
  }

  /**
   * @param string $hostUrl
   * @return $this
   */
  public function setHostUrl( $hostUrl )
  {
    $this->hostUrl = $hostUrl;

    return $this;
  }

  /**
   * @return string
   */
  public function getHostUrl()
  {
    return $this->hostUrl;
  }

  public function setPublicSslKeys(string $sslKeys)
  {
      $this->sslKeys = $sslKeys;

      return $this;
  }

    public function useOnlyDemoPublicKey()
    {
        $this->sslKeys = self::DEMO_SID_PUBLIC_KEY;

        return $this;
    }

    public function useOnlyLivePublicKey()
    {
        $this->sslKeys = self::RP_API_PUBLIC_KEY_VALID_FROM_2016_12_20_TO_2020_01_19.";".self::RP_API_PUBLIC_KEY_VALID_FROM_2019_11_01_TO_2021_11_05;

        return $this;
    }

  public function getPublicSslKeys()
  {
      if($this->sslKeys === null)
      {
          $this->sslKeys = self::DEMO_SID_PUBLIC_KEY.";".self::RP_API_PUBLIC_KEY_VALID_FROM_2016_12_20_TO_2020_01_19.";".self::RP_API_PUBLIC_KEY_VALID_FROM_2019_11_01_TO_2021_11_05;
      }
      return $this->sslKeys;
  }
}
