<?php
namespace Sk\SmartId\Api;

use Sk\SmartId\Exception\InvalidParametersException;

abstract class SmartIdRequestBuilder
{
  /**
   * @var SmartIdConnector
   */
  private $connector;

  /**
   * @var SessionStatusPoller
   */
  private $sessionStatusPoller;

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
  private $networkInterface;

  /**
   * @param SmartIdConnector $connector
   * @param SessionStatusPoller $sessionStatusPoller
   */
  public function __construct( SmartIdConnector $connector, SessionStatusPoller $sessionStatusPoller )
  {
    $this->connector = $connector;
    $this->sessionStatusPoller = $sessionStatusPoller;
  }

  /**
   * @param string $relyingPartyUUID
   * @return $this
   */
  public function withRelyingPartyUUID( $relyingPartyUUID )
  {
    $this->relyingPartyUUID = $relyingPartyUUID;
    return $this;
  }

  /**
   * @param string $relyingPartyName
   * @return $this
   */
  public function withRelyingPartyName( $relyingPartyName )
  {
    $this->relyingPartyName = $relyingPartyName;
    return $this;
  }

  /**
   * @param string $networkInterface
   * @return $this
   */
  public function withNetworkInterface( $networkInterface )
  {
    $this->networkInterface = $networkInterface;
    return $this;
  }

  /**
   * @return SmartIdConnector
   */
  public function getConnector()
  {
    return $this->connector;
  }

  /**
   * @return SessionStatusPoller
   */
  public function getSessionStatusPoller()
  {
    return $this->sessionStatusPoller;
  }

  /**
   * @return string
   */
  public function getRelyingPartyUUID()
  {
    return $this->relyingPartyUUID;
  }

  /**
   * @return string
   */
  public function getRelyingPartyName()
  {
    return $this->relyingPartyName;
  }

  /**
   * @return string
   */
  public function getNetworkInterface()
  {
    return $this->networkInterface;
  }

  /**
   * @throws InvalidParametersException
   */
  protected function validateParameters()
  {
    if ( !isset( $this->relyingPartyUUID ) )
    {
      throw new InvalidParametersException( 'Relying Party UUID parameter must be set' );
    }

    if ( !isset( $this->relyingPartyName ) )
    {
      throw new InvalidParametersException( 'Relying Party Name parameter must be set' );
    }
  }
}
