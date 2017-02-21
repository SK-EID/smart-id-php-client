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
    $builder   = new AuthenticationRequestBuilder( $connector );
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
}