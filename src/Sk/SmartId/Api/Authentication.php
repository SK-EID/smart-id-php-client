<?php
namespace Sk\SmartId\Api;

class Authentication extends AbstractApi
{
  /**
   * @return AuthenticationRequestBuilder
   */
  public function createAuthentication()
  {
    $connector = new SmartIdRestConnector( $this->client->getHostUrl() );
    $sessionStatusPoller = $this->createSessionStatusPoller( $connector );
    $builder = new AuthenticationRequestBuilder( $connector, $sessionStatusPoller );
    $this->populateBuilderFields( $builder );

    return $builder;
  }

  /**
   * @param SmartIdRequestBuilder $builder
   */
  private function populateBuilderFields( SmartIdRequestBuilder $builder )
  {
    $builder
        ->withRelyingPartyUUID( $this->client->getRelyingPartyUUID() )
        ->withRelyingPartyName( $this->client->getRelyingPartyName() );
  }

  /**
   * @param SmartIdRestConnector $connector
   * @return SessionStatusPoller
   */
  private function createSessionStatusPoller( SmartIdRestConnector $connector )
  {
    $sessionStatusPoller = new SessionStatusPoller( $connector );
    return $sessionStatusPoller;
  }
}