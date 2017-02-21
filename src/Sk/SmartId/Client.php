<?php
namespace Sk\SmartId;

use InvalidArgumentException;
use Sk\SmartId\Api\AbstractApi;
use Sk\SmartId\Api\ApiType;
use Sk\SmartId\Api\Authentication;

class Client
{
  const VERSION = '5.0';

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
    if ( ! isset( $this->apis['authentication'] ) )
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
}