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
namespace Sk\SmartId\Util;
defined('CURLOPT_PINNEDPUBLICKEY') || define('CURLOPT_PINNEDPUBLICKEY', 10230);

use Exception;

class Curl
{

  const
      GET = 1,
      POST = 2,
      PUT = 3;

  protected
      $curl,
      $rawData,
      $cookies = array(),
      $postFields = array(),
      $followLocation = 0,
      $requestMethod = self::GET,
      $importCookies = false,
      $includeHeaders = false,
      $curlTimeout = 600,
      $server_ssl_public_keys;

  /**
   * @throws Exception
   */
  public function __construct()
  {
    if ( !function_exists( 'curl_init' ) )
    {
      throw new Exception( 'curl not installed' );
    }

    $this->curl = curl_init();
  }

  /**
   * @param string $url
   * @param array $params
   *
   * @return Curl
   */
  public function curlGet(string $url, array $params = array() ): Curl
  {
    if ( count( $params ) )
    {
      $url .= '?' . $this->generatePostFields( $params );
    }

    $this->setCurlParam( CURLOPT_URL, $url );
    $this->requestMethod = self::GET;

    return $this;
  }

  /**
   * @param $followLocation
   * @return Curl
   */
  public function followLocation( $followLocation ): Curl
  {
    $this->followLocation = ( $followLocation ? 1 : 0 );

    return $this;
  }

  /**
   * @param $paramsId
   * @param $paramsValue
   * @return Curl
   */
  public function setCurlParam( $paramsId, $paramsValue ): Curl
  {
    curl_setopt( $this->curl, $paramsId, $paramsValue );

    return $this;
  }

  /**
   * @param string $url
   * @param array $postData
   * @param null $rawData
   * @return Curl
   */
  public function curlPost(string $url, array $postData = array(), $rawData = null ): Curl
  {
    $this->setCurlParam( CURLOPT_URL, $url );
    $this->requestMethod = self::POST;
    $this->postFields = $postData;
    $this->rawData = $rawData;

    return $this;
  }

  /**
   * @param string $url
   * @param array $postData
   * @param null $rawData
   * @return Curl
   */
  public function curlPut(string $url, array $postData = array(), $rawData = null ): Curl
  {
    $this->setCurlParam( CURLOPT_URL, $url );
    $this->requestMethod = self::PUT;
    $this->postFields = $postData;
    $this->rawData = $rawData;

    return $this;
  }

  /**
   * @param string $savePath
   */
  public function download(string $savePath )
  {
    $file = fopen( $savePath, 'w' );

    curl_setopt( $this->curl, CURLOPT_FILE, $file );

    $this->sendRequest();

    $this->closeRequest();

    fclose( $file );
  }

  /**
   * @return bool|string
   */
  public function fetch()
  {
    curl_setopt( $this->curl, CURLOPT_RETURNTRANSFER, 1 );

      return $this->sendRequest();
  }

  public function getCookies(): array
  {
    $this->importCookies();

    curl_setopt( $this->curl, CURLOPT_RETURNTRANSFER, 1 );

    $result = $this->sendRequest();

    $this->closeRequest();

    return $this->exportCookies( $result );
  }

  public function setCookies( $cookies )
  {
    $this->cookies = $cookies;
  }

  public function importCookies( $importCookies = true )
  {
    $this->importCookies = (bool)$importCookies;
  }

  public function includeHeaders( $includeHeaders = true )
  {
    $this->includeHeaders = (bool)$includeHeaders;
  }

  protected function sendRequest()
  {
    curl_setopt( $this->curl, CURLOPT_HEADER, ( ( $this->includeHeaders || $this->importCookies ) ? 1 : 0 ) );
    curl_setopt( $this->curl, CURLOPT_FOLLOWLOCATION, $this->followLocation );
    curl_setopt( $this->curl, CURLOPT_TIMEOUT, $this->curlTimeout );
    curl_setopt( $this->curl, CURLOPT_SSL_VERIFYPEER, false );
    if (isset($this->server_ssl_public_keys) and !empty($this->server_ssl_public_keys)) {

        curl_setopt( $this->curl, CURLOPT_PINNEDPUBLICKEY, $this->server_ssl_public_keys);
    }

    if ( self::POST === $this->requestMethod )
    {
      // Send POST request
      curl_setopt( $this->curl, CURLOPT_POST, 1 );
      curl_setopt( $this->curl, CURLOPT_POSTFIELDS, $this->getPostFieldsString() );
    }
    elseif ( self::PUT === $this->requestMethod )
    {
      // Send PUT request
      curl_setopt( $this->curl, CURLOPT_CUSTOMREQUEST, 'PUT' );
      curl_setopt( $this->curl, CURLOPT_HTTPHEADER,
          array('Content-Length: ' . strlen( $this->getPostFieldsString() )) );
      curl_setopt( $this->curl, CURLOPT_POSTFIELDS, $this->getPostFieldsString() );
    }

    if ( count( $this->cookies ) )
    {
      // Send cookies
      curl_setopt( $this->curl, CURLOPT_COOKIE, $this->generateCookies( $this->cookies ) );
    }

    return curl_exec( $this->curl );
  }

  public function closeRequest()
  {
    curl_close( $this->curl );
  }

  /**
   * Return mail headers
   */
  public function getHeaders( $source, $continue = true )
  {
    if ( false !== ( $separator_pos = strpos( $source, "\r\n\r\n" ) ) )
    {
      if ( $continue && false !== strpos( $source, 'HTTP/1.1 100 Continue' ) )
      {
        $source = trim( substr( $source, $separator_pos + 4 ) );
        $source = $this->getHeaders( $source, false );
      }
      else
      {
        $source = trim( substr( $source, 0, $separator_pos ) );
      }

      return $source;
    }

    return '';
  }

  public function &removeHeaders( $source, $continue = true )
  {
    if ( false !== ( $separator_pos = strpos( $source, "\r\n\r\n" ) ) )
    {
      if ( $continue && false !== strpos( $source, 'HTTP/1.1 100 Continue' ) )
      {
        $source = trim( substr( $source, $separator_pos + 4 ) );
        $source =& $this->removeHeaders( $source, false );
      }
      else
      {
        $source = trim( substr( $source, $separator_pos + 4 ) );
      }
    }

    return $source;
  }

  /**
   * If cookies were sent, save them
   */
  public function exportCookies( $source ): array
  {
    $cookies = array();

    if ( preg_match_all( '#Set-Cookie:\s*([^=]+)=([^;]+)#i', $source, $matches ) )
    {
      for ( $i = 0, $cnt = count( $matches[ 1 ] ); $i < $cnt; ++$i )
      {
        $cookies[ trim( $matches[ 1 ][ $i ] ) ] = trim( $matches[ 2 ][ $i ] );
      }
    }

    return $cookies;
  }

  public function getPostFieldsString(): string
  {
    if ( !empty( $this->rawData ) )
    {
      return $this->rawData;
    }

    return $this->generatePostFields( $this->postFields );
  }

  /**
   * @param array $inputArray
   * @return string
   */
  public function generatePostFields( array $inputArray ): string
  {
    return http_build_query( $inputArray );
  }

  public function generateCookies( $inputArray ): string
  {
    $cookies = array();

    foreach ( $inputArray as $field => $value )
    {
      $cookies[] = $field . '=' . $value;
    }

    return implode( ';', $cookies );
  }

  public function prepareCookies()
  {
    if ( count( $this->cookies ) )
    {
      return implode( ';', $this->cookies );
    }
    else
    {
      return false;
    }
  }

  public function getCurlTimeout(): int
  {
    return $this->curlTimeout;
  }

  public function setCurlTimeout( $curlTimeout )
  {
    $this->curlTimeout = $curlTimeout;
  }

  /**
   * @return bool|string
   */
  public function getError()
  {
    if ( curl_errno( $this->curl ) )
    {
      return curl_error( $this->curl );
    }

    return false;
  }

  public function setPublicSslKeys(?string $publicSslKeys)
  {
      $this->server_ssl_public_keys = $publicSslKeys;
  }

  public function setPublicKeysFromArray(array $public_keys)
  {
      $this->server_ssl_public_keys = "";
      foreach ($public_keys as $public_key)
      {
          $this->server_ssl_public_keys .= $public_key.";";
      }
      $this->server_ssl_public_keys = substr($this->server_ssl_public_keys, 0, strlen($this->server_ssl_public_keys)-1);
  }

    /**
   * @param int $option
   * @return array|mixed
   */
  public function getCurlInfo( $option = null )
  {
    if ( null !== $option )
    {
      return curl_getinfo( $this->curl, $option );
    }

    return curl_getinfo( $this->curl );
  }
}
