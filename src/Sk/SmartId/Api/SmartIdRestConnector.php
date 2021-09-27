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
namespace Sk\SmartId\Api;

use Exception;
use Sk\SmartId\Api\Data\AuthenticationSessionRequest;
use Sk\SmartId\Api\Data\AuthenticationSessionResponse;
use Sk\SmartId\Api\Data\SemanticsIdentifier;
use Sk\SmartId\Api\Data\SessionStatus;
use Sk\SmartId\Api\Data\SessionStatusRequest;
use Sk\SmartId\Client;
use Sk\SmartId\Exception\NotFoundException;
use Sk\SmartId\Exception\SessionNotFoundException;
use Sk\SmartId\Exception\SmartIdException;
use Sk\SmartId\Exception\UserAccountNotFoundException;
use Sk\SmartId\Util\Curl;

class SmartIdRestConnector implements SmartIdConnector
{
  const AUTHENTICATE_BY_DOCUMENT_NUMBER_PATH = '/authentication/document/{documentNumber}';
  const SESSION_STATUS_URI = '/session/{sessionId}';
  const AUTHENTICATION_BY_SEMANTICS_IDENTIFIER_PATH = '/authentication/etsi/{semantics-identifier}';

  const RESPONSE_ERROR_CODES = array(
      503 => 'Limit exceeded',
      403 => 'Forbidden!',
      401 => 'Unauthorized',

      580 => 'System is under maintenance, retry later',
      480 => 'The client is old and not supported any more. Relying Party must contact customer support.',
      472 => 'Person should view app or self-service portal now.',
      471 => 'No suitable account of requested type found, but user has some other accounts.',
  );

  /**
   * @var string
   */
  private $endpointUrl;

  /**
   * @var Curl
   */
  private $curl;

  private $publicSslKeys;

  /**
   * @param string $endpointUrl
   */
  public function __construct(string $endpointUrl )
  {
    $this->endpointUrl = $endpointUrl;
  }

  /**
   * @param string $documentNumber
   * @param AuthenticationSessionRequest $request
   * @return AuthenticationSessionResponse
   * @throws Exception
   */
  public function authenticate(string $documentNumber, AuthenticationSessionRequest $request ): AuthenticationSessionResponse
  {
    $url = rtrim( $this->endpointUrl, '/' ) . self::AUTHENTICATE_BY_DOCUMENT_NUMBER_PATH;
    $url = str_replace( '{documentNumber}', $documentNumber, $url );
    return $this->postAuthenticationRequest( $url, $request );
  }

    /**
     * @param SemanticsIdentifier $semanticsIdentifier
     * @param AuthenticationSessionRequest $request
     * @return AuthenticationSessionResponse
     * @throws Exception
     */
    function authenticateWithSemanticsIdentifier(SemanticsIdentifier $semanticsIdentifier, AuthenticationSessionRequest $request) :AuthenticationSessionResponse
    {
        $url = rtrim( $this->endpointUrl, '/' ) . self::AUTHENTICATION_BY_SEMANTICS_IDENTIFIER_PATH;
        $url = str_replace( array(
            '{semantics-identifier}'
        ), array(
            $semanticsIdentifier->asString()
        ), $url );
        return $this->postAuthenticationRequest($url, $request);
    }

  /**
   * @param SessionStatusRequest $request
   * @throws SessionNotFoundException
   * @throws Exception
   * @return SessionStatus
   */
  public function getSessionStatus( SessionStatusRequest $request ) : SessionStatus
  {
    $url = rtrim( $this->endpointUrl, '/' ) . self::SESSION_STATUS_URI;
    $url = str_replace( '{sessionId}', $request->getSessionId(), $url );
    try
    {
        return $this->getRequest( $url, $request->toArray(), 'Sk\SmartId\Api\Data\SessionStatus' );
    }
    catch ( NotFoundException $e )
    {
      throw new SessionNotFoundException();
    }
  }

  /**
   * @param string $url
   * @param AuthenticationSessionRequest $request
   * @return AuthenticationSessionResponse
   * @throws Exception
   * @throws UserAccountNotFoundException
   */
  private function postAuthenticationRequest(string $url, AuthenticationSessionRequest $request ): AuthenticationSessionResponse
  {
    try
    {
      return $this->postRequest( $url, $request->toArray(), 'Sk\SmartId\Api\Data\AuthenticationSessionResponse' );
    }
    catch ( NotFoundException $e )
    {
      throw new UserAccountNotFoundException($e->getMessage());
    }
  }

  // TODO signature status request response type?

  /**
   * @param string $url
   * @param array $params
   * @param string $responseType
   * @return mixed
   * @throws Exception
   */
  function postRequest(string $url, array $params, string $responseType )
  {
    $this->curl = new Curl();
    $this->curl->setPublicSslKeys($this->publicSslKeys);
    $this->setNetworkInterface( $params );
    $this->curl->curlPost( $url, array(), json_encode( $params ) );
    $this->curl->setCurlParam( CURLOPT_HTTPHEADER, array('content-type: application/json','User-Agent: smart-id-php-client/'.Client::VERSION.' (PHP/'.phpversion().')') );
    return $this->request( $url, $responseType );
  }

  /**
   * @param string $url
   * @param array $params
   * @param string $responseType
   * @return mixed
   * @throws Exception
   */
  function getRequest(string $url, array $params, string $responseType )
  {
    $this->curl = new Curl();
    $this->curl->setPublicSslKeys($this->publicSslKeys);
    $this->setNetworkInterface( $params );
    $this->curl->curlGet( $url, $params );
    $this->curl->setCurlParam( CURLOPT_HTTPHEADER, array('User-Agent: smart-id-php-client/'.Client::VERSION.' (PHP/'.phpversion().')') );
    return $this->request( $url, $responseType );
  }

  /**
   * @param string $url
   * @param string $responseType
   * @return mixed
   * @throws NotFoundException
   * @throws SmartIdException
   */
  private function request(string $url, string $responseType )
  {
    $rawResponse = $this->curl->fetch();

    if ( false !== ( $error = $this->curl->getError() ) )
    {
      throw new SmartIdException( $error );
    }

    $httpCode = $this->curl->getCurlInfo( CURLINFO_HTTP_CODE );

    $this->curl->closeRequest();

    if ( array_key_exists( $httpCode, self::RESPONSE_ERROR_CODES ) )
    {
      throw new SmartIdException( self::RESPONSE_ERROR_CODES[ $httpCode ], $httpCode );
    }

    if ( 404 == $httpCode )
    {
      throw new NotFoundException( 'User account not found for URI ' . $url );
    }

    return $this->getResponse( $rawResponse, $responseType );
  }

  /**
   * @param string $rawResponse
   * @param string $responseType
   * @return mixed
   */
  private function getResponse(string $rawResponse, string $responseType )
  {
    $preparedResponse = json_decode( $rawResponse, true );

    return new $responseType( $preparedResponse );
  }

  /**
   * @param array $params
   */
  private function setNetworkInterface( array &$params )
  {
    if ( isset( $params[ 'networkInterface' ] ) )
    {
      $this->curl->setCurlParam( CURLOPT_INTERFACE, $params[ 'networkInterface' ] );
      unset( $params[ 'networkInterface' ] );
    }
  }

  public function setPublicSslKeys(?string $sslKeys)
  {
      $this->publicSslKeys = $sslKeys;
  }
}
