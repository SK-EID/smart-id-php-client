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
  // THIS NEEDS TO REFLECT THE CURRENT smart-id-php-client version
  // IT IS EITHER A VERSION (like 2.2) OR A FUTURE VERSION (2.2.SNAPSHOT)
  const VERSION = '2.2.2';

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
   * @return AbstractApi
   * @throws InvalidArgumentException
   */
  public function api( string $apiName )
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
  public function authentication(): Authentication
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
  public function setRelyingPartyUUID(string $relyingPartyUUID ): Client
  {
    $this->relyingPartyUUID = $relyingPartyUUID;

    return $this;
  }

  /**
   * @return string
   */
  public function getRelyingPartyUUID(): string
  {
    return $this->relyingPartyUUID;
  }

  /**
   * @param string $relyingPartyName
   * @return $this
   */
  public function setRelyingPartyName(string $relyingPartyName ): Client
  {
    $this->relyingPartyName = $relyingPartyName;

    return $this;
  }

  /**
   * @return string
   */
  public function getRelyingPartyName(): string
  {
    return $this->relyingPartyName;
  }

  /**
   * @param string $hostUrl
   * @return $this
   */
  public function setHostUrl(string $hostUrl ): Client
  {
    $this->hostUrl = $hostUrl;

    return $this;
  }

  /**
   * @return string
   */
  public function getHostUrl(): string
  {
    return $this->hostUrl;
  }

  public function setPublicSslKeys(string $sslKeys): Client
  {
      $this->sslKeys = $sslKeys;

      return $this;
  }

  public function getPublicSslKeys(): ?string
  {
      return $this->sslKeys;
  }

}
