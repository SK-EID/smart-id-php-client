<?php
namespace Sk\SmartId\Tests\Rest;

use PHPUnit\Framework\TestCase;
use Sk\SmartId\Api\Data\AuthenticationSessionRequest;
use Sk\SmartId\Api\Data\AuthenticationSessionResponse;
use Sk\SmartId\Api\Data\DigestCalculator;
use Sk\SmartId\Api\Data\HashType;
use Sk\SmartId\Api\Data\SessionStatus;
use Sk\SmartId\Api\Data\SessionStatusCode;
use Sk\SmartId\Api\Data\SessionStatusRequest;
use Sk\SmartId\Api\SmartIdConnector;
use Sk\SmartId\Api\SmartIdRestConnector;
use Sk\SmartId\Tests\Api\DummyData;

class SmartIdRestIntegrationTest extends TestCase
{
  /**
   * @var SmartIdConnector
   */
  private $connector;

  protected function setUp()
  {
    $this->connector = new SmartIdRestConnector( DummyData::TEST_URL );
  }

  /**
   * @after
   */
  public function waitForMobileAppToFinish()
  {
    sleep( 10 );
  }

  /**
   * @test
   */
  public function authenticate()
  {
    $authenticationSessionResponse = $this->fetchAuthenticationSession();
    $sessionStatus = $this->pollSessionStatus( $authenticationSessionResponse->getSessionID() );
    $this->assertAuthenticationResultCreated( $sessionStatus );
  }

  /**
   * @return AuthenticationSessionResponse
   */
  private function fetchAuthenticationSession()
  {
    $request = $this->createAuthenticationSessionRequest();
    $authenticationSessionResponse = $this->connector->authenticate( DummyData::VALID_DOCUMENT_NUMBER, $request );
    $this->assertNotNull( $authenticationSessionResponse );
    $this->assertNotEmpty( $authenticationSessionResponse->getSessionID() );
    return $authenticationSessionResponse;
  }

  /**
   * @return AuthenticationSessionRequest
   */
  private function createAuthenticationSessionRequest()
  {
    $authenticationSessionRequest = new AuthenticationSessionRequest();
    $authenticationSessionRequest
        ->setRelyingPartyUUID( DummyData::DEMO_RELYING_PARTY_UUID )
        ->setRelyingPartyName( DummyData::DEMO_RELYING_PARTY_NAME )
        ->setCertificateLevel( DummyData::CERTIFICATE_LEVEL )
        ->setHashType( HashType::SHA512 );
    $hashInBase64 = $this->calculateHashInBase64( DummyData::SIGNABLE_TEXT );
    $authenticationSessionRequest->setHash( $hashInBase64 );
    return $authenticationSessionRequest;
  }

  /**
   * @param string $dataToSign
   * @return string
   */
  private function calculateHashInBase64( $dataToSign )
  {
    $digestValue = DigestCalculator::calculateDigest( $dataToSign, HashType::SHA512 );
    return base64_encode( $digestValue );
  }

  /**
   * @param string $sessionId
   * @return SessionStatus
   */
  private function pollSessionStatus( $sessionId )
  {
    /** @var SessionStatus $sessionStatus */
    $sessionStatus = null;
    while ( $sessionStatus === null || strcasecmp( SessionStatusCode::RUNNING, $sessionStatus->getState() ) === 0 )
    {
      $request = $this->createSessionStatusRequest( $sessionId );
      $this->assertEquals( array('timeoutMs' => 1000), $request->toArray() );
      $sessionStatus = $this->connector->getSessionStatus( $request );
      sleep( 1 );
    }
    $this->assertEquals( SessionStatusCode::COMPLETE, $sessionStatus->getState() );
    return $sessionStatus;
  }

  /**
   * @param SessionStatus|null $sessionStatus
   */
  private function assertAuthenticationResultCreated( SessionStatus $sessionStatus = null )
  {
    $this->assertNotNull( $sessionStatus );

    $this->assertNotEmpty( $sessionStatus->getResult() );
    $this->assertNotEmpty( $sessionStatus->getSignature()->getValue() );
    $this->assertNotEmpty( $sessionStatus->getCert()->getValue() );
    $this->assertNotEmpty( $sessionStatus->getCert()->getCertificateLevel() );
  }

  /**
   * @param string $sessionId
   * @return SessionStatusRequest
   */
  private function createSessionStatusRequest( $sessionId )
  {
    $sessionStatusRequest = new SessionStatusRequest( $sessionId );
    $sessionStatusRequest->setSessionStatusResponseSocketTimeoutMs( 1000 );
    return $sessionStatusRequest;
  }
}
