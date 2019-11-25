<?php


namespace Sk\SmartId\Tests\Api;


use PHPUnit\Framework\TestCase;
use Sk\SmartId\Api\AuthenticationRequestBuilder;
use Sk\SmartId\Api\Data\AuthenticationSessionResponse;
use Sk\SmartId\Api\Data\SessionCertificate;
use Sk\SmartId\Api\Data\SessionResult;
use Sk\SmartId\Api\Data\SessionSignature;
use Sk\SmartId\Api\Data\SessionStatus;
use Sk\SmartId\Api\Data\SignableData;
use Sk\SmartId\Api\SessionStatusPoller;
use Sk\SmartId\Tests\Rest\SmartIdConnectorSpy;



class NetworkInterfaceTest extends TestCase
{

    private $NETWORK_INTERFACE = "eth0";
    /**
     * @test
     */
    public function createAuthenticationSessionRequest_withNetworkInterface_assertInterfaceInRequest()
    {
        $connector = new SmartIdConnectorSpy();
        $this->stubConnectorSpyWithResponses($connector);
        $this->buildAndMakeAuthenticationRequest($connector);
        self::assertEquals($this->NETWORK_INTERFACE, $connector->authenticationSessionRequestUsed->toArray()['networkInterface']);
    }

    /**
     * @test
     */
    public function authenticationRequestBuilder_withNetworkInterface_assertInterfaceInSessionStatusFetcher()
    {
        $connector = new SmartIdConnectorSpy();
        $this->stubConnectorSpyWithResponses($connector);
        $this->buildAndMakeAuthenticationRequest($connector);
        self::assertEquals($this->NETWORK_INTERFACE, $connector->sessionStatusRequestUsed->toArray()['networkInterface']);
    }

    private function stubConnectorSpyWithResponses(SmartIdConnectorSpy $smartIdConnectorSpy)
    {
        $authSessionResponse = new AuthenticationSessionResponse(array());
        $authSessionResponse->setSessionID("12345");
        $sessionStatus = new SessionStatus();
        $sessionStatus->setResult(new SessionResult(array("endResult"=>"OK")));
        $sessionStatus->setSignature(new SessionSignature());
        $sessionStatus->setCert(new SessionCertificate());
        $smartIdConnectorSpy->authenticationSessionResponseToRespond = $authSessionResponse;
        $smartIdConnectorSpy->sessionStatusToRespond = $sessionStatus;
    }

    private function buildAndMakeAuthenticationRequest(SmartIdConnectorSpy $connector)
    {
        $sessionStatusPoller = new SessionStatusPoller($connector);
        $builder = new AuthenticationRequestBuilder($connector, $sessionStatusPoller);
        $builder
            ->withRelyingPartyUUID( DummyData::DEMO_RELYING_PARTY_UUID )
            ->withRelyingPartyName( DummyData::DEMO_RELYING_PARTY_NAME )
            ->withDocumentNumber( DummyData::VALID_DOCUMENT_NUMBER )
            ->withCertificateLevel( DummyData::CERTIFICATE_LEVEL )
            ->withNetworkInterface( $this->NETWORK_INTERFACE )
            ->withSignableData(new SignableData("TERE"))
            ->authenticate();
    }
}