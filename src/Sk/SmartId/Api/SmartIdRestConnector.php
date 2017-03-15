<?php
namespace Sk\SmartId\Api;

use Sk\SmartId\Api\Data\AuthenticationSessionRequest;
use Sk\SmartId\Api\Data\AuthenticationSessionResponse;
use Sk\SmartId\Api\Data\NationalIdentity;
use Sk\SmartId\Api\Data\SessionStatus;
use Sk\SmartId\Api\Data\SessionStatusRequest;
use Sk\SmartId\Exception\NotFoundException;
use Sk\SmartId\Exception\SessionNotFoundException;
use Sk\SmartId\Exception\SmartIdException;
use Sk\SmartId\Exception\UserAccountNotFoundException;
use Sk\SmartId\Util\Curl;

class SmartIdRestConnector implements SmartIdConnector
{
  const AUTHENTICATE_BY_DOCUMENT_NUMBER_PATH = '/authentication/document/{documentNumber}';
  const AUTHENTICATE_BY_NATIONAL_IDENTITY_PATH = '/authentication/pno/{country}/{nationalIdentityNumber}';
  const SESSION_STATUS_URI = '/session/{sessionId}';

  /**
   * @var string
   */
  private $endpointUrl;

  /**
   * @var Curl
   */
  private $curl;

  /**
   * @param string $endpointUrl
   */
  public function __construct( $endpointUrl )
  {
    $this->endpointUrl = $endpointUrl;
  }

  /**
   * @param string $documentNumber
   * @param AuthenticationSessionRequest $request
   * @return AuthenticationSessionResponse
   */
  public function authenticate( $documentNumber, AuthenticationSessionRequest $request )
  {
    $url = rtrim( $this->endpointUrl, '/' ) . self::AUTHENTICATE_BY_DOCUMENT_NUMBER_PATH;
    $url = str_replace( '{documentNumber}', $documentNumber, $url );
    return $this->postAuthenticationRequest( $url, $request );
  }

  /**
   * @param NationalIdentity $identity
   * @param AuthenticationSessionRequest $request
   * @return AuthenticationSessionResponse
   */
  public function authenticateWithIdentity( NationalIdentity $identity, AuthenticationSessionRequest $request )
  {
    $url = rtrim( $this->endpointUrl, '/' ) . self::AUTHENTICATE_BY_NATIONAL_IDENTITY_PATH;
    $url = str_replace( array(
        '{country}',
        '{nationalIdentityNumber}',
    ), array(
        $identity->getCountryCode(),
        $identity->getNationalIdentityNumber(),
    ), $url );
    return $this->postAuthenticationRequest( $url, $request );
  }

  /**
   * @param SessionStatusRequest $request
   * @throws SessionNotFoundException
   * @return SessionStatus
   */
  public function getSessionStatus( SessionStatusRequest $request )
  {
    $url = rtrim( $this->endpointUrl, '/' ) . self::SESSION_STATUS_URI;
    $url = str_replace( '{sessionId}', $request->getSessionId(), $url );
    $params = $this->getResponseSocketOpenTimeUrlParameter( $request );
    try
    {
      $sessionStatus = $this->getRequest( $url, $params, 'Sk\SmartId\Api\Data\SessionStatus' );
      return $sessionStatus;
    }
    catch ( NotFoundException $e )
    {
      throw new SessionNotFoundException();
    }
  }

  /**
   * @param string $url
   * @param AuthenticationSessionRequest $request
   * @throws UserAccountNotFoundException
   * @return AuthenticationSessionResponse
   */
  private function postAuthenticationRequest( $url, AuthenticationSessionRequest $request )
  {
    try
    {
      return $this->postRequest( $url, $request->toArray(), 'Sk\SmartId\Api\Data\AuthenticationSessionResponse' );
    }
    catch ( NotFoundException $e )
    {
      throw new UserAccountNotFoundException();
    }
  }

  /**
   * @param SessionStatusRequest $request
   * @return array
   */
  private function getResponseSocketOpenTimeUrlParameter( SessionStatusRequest $request )
  {
    $params = array();
    if ( $request->isSessionStatusResponseSocketOpenTimeoutSet() )
    {
      $params['timeoutMs'] = $request->getSessionStatusResponseSocketOpenTimeoutMs();
    }
    return $params;
  }

  /**
   * @param string $url
   * @param array $params
   * @param string $responseType
   * @return mixed
   */
  private function postRequest( $url, array $params = array(), $responseType )
  {
    $this->curl = new Curl();
    $this->curl->curlPost( $url, array(), json_encode( $params ) );
    $this->curl->setCurlParam( CURLOPT_HTTPHEADER, array(
        'content-type: application/json',
    ) );
    return $this->request( $url, $responseType );
  }

  /**
   * @param string $url
   * @param array $params
   * @param string $responseType
   * @return mixed
   */
  private function getRequest( $url, array $params = array(), $responseType )
  {
    $this->curl = new Curl();
    $this->curl->curlGet( $url, $params );
    return $this->request( $url, $responseType );
  }

  /**
   * @param string $url
   * @param string $responseType
   * @throws SmartIdException
   * @throws NotFoundException
   * @return mixed
   */
  private function request( $url, $responseType )
  {
    $rawResponse = $this->curl->fetch();

    if ( false !== ( $error = $this->curl->getError() ) )
    {
      throw new SmartIdException( $error );
    }

    $httpCode = $this->curl->getCurlInfo( CURLINFO_HTTP_CODE );

    $this->curl->closeRequest();

    if ( 503 == $httpCode )
    {
      throw new SmartIdException( 'Limit exceeded' );
    }

    if ( 403 == $httpCode )
    {
      throw new SmartIdException( 'Forbidden!' );
    }

    $response = $this->getResponse( $rawResponse, $responseType );

    if ( 404 == $httpCode )
    {
      throw new NotFoundException( 'User account not found for URI ' . $url );
    }

    return $response;
  }

  /**
   * @param string $rawResponse
   * @param string $responseType
   * @return mixed
   */
  private function getResponse( $rawResponse, $responseType )
  {
    $preparedResponse = json_decode( $rawResponse, true );
    return new $responseType( $preparedResponse );
  }
}