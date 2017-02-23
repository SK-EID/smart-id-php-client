<?php
namespace Sk\SmartId\Tests\Rest;

use PHPUnit\Framework\TestCase;
use Sk\SmartId\Api\Data\SessionStatus;
use Sk\SmartId\Api\Data\SessionStatusCode;
use Sk\SmartId\Api\SessionStatusPoller;
use Sk\SmartId\Tests\Api\DummyData;

class SessionStatusPollerTest extends TestCase
{
  /**
   * @var SmartIdConnectorStub
   */
  private $connector;

  /**
   * @var SessionStatusPoller
   */
  private $poller;

  protected function setUp()
  {
    $this->connector = new SmartIdConnectorStub();
    $this->poller = new SessionStatusPoller( $this->connector );
    $this->poller->setPollingSleepTimeoutMs( 1 );
  }

  /**
   * @test
   */
  public function getFirstCompleteResponse()
  {
    $this->connector->responses[] = $this->createCompleteSessionStatus();
    $status = $this->poller->fetchFinalSessionStatus( '97f5058e-e308-4c83-ac14-7712b0eb9d86' );
    $this->assertEquals( '97f5058e-e308-4c83-ac14-7712b0eb9d86', $this->connector->sessionIdUsed );
    $this->assertEquals( 1, $this->connector->responseNumber );
    $this->assertCompleteStateReceived( $status );
  }

  /**
   * @test
   */
  public function pollAndGetThirdCompleteResponse()
  {
    $this->connector->responses[] = $this->createRunningSessionStatus();
    $this->connector->responses[] = $this->createRunningSessionStatus();
    $this->connector->responses[] = $this->createCompleteSessionStatus();
    $status = $this->poller->fetchFinalSessionStatus( '97f5058e-e308-4c83-ac14-7712b0eb9d86' );
    $this->assertEquals( '97f5058e-e308-4c83-ac14-7712b0eb9d86', $this->connector->sessionIdUsed );
    $this->assertEquals( 3, $this->connector->responseNumber );
    $this->assertCompleteStateReceived( $status );
  }

  /**
   * @test
   */
  public function setPollingSleepTime()
  {
    $this->poller->setPollingSleepTimeoutMs( 200 );
    $this->addMultipleRunningSessionResponses( 5 );
    $this->connector->responses[] = $this->createCompleteSessionStatus();
    $duration = $this->measurePollingDuration();
    $this->assertTrue( $duration > 1000 );
    $this->assertTrue( $duration < 1100 );
  }

  /**
   * @test
   */
  public function setResponseSocketOpenTime()
  {
    $this->poller->setSessionStatusResponseSocketOpenTimeoutMs( 2 * 60 * 1000 ); // 2 min
    $this->connector->responses[] = $this->createCompleteSessionStatus();
    $status = $this->poller->fetchFinalSessionStatus( '97f5058e-e308-4c83-ac14-7712b0eb9d86' );
    $this->assertCompleteStateReceived( $status );
    $this->assertTrue( $this->connector->requestUsed->isSessionStatusResponseSocketOpenTimeoutSet() );
    $this->assertEquals( 2 * 60 * 1000, $this->connector->requestUsed->getSessionStatusResponseSocketOpenTimeoutMs() );
  }

  /**
   * @test
   */
  public function responseSocketOpenTimeShouldNotBeSetByDefault()
  {
    $this->connector->responses[] = $this->createCompleteSessionStatus();
    $status = $this->poller->fetchFinalSessionStatus( '97f5058e-e308-4c83-ac14-7712b0eb9d86' );
    $this->assertCompleteStateReceived( $status );
    $this->assertFalse( $this->connector->requestUsed->isSessionStatusResponseSocketOpenTimeoutSet() );
  }

  /**
   * @test
   * @expectedException \Sk\SmartId\Exception\UserRefusedException
   */
  public function getUserRefusedResponse_shouldThrowException()
  {
    $this->connector->responses[] = DummyData::createUserRefusedSessionStatus();
    $this->poller->fetchFinalSessionStatus( '97f5058e-e308-4c83-ac14-7712b0eb9d86' );
  }

  /**
   * @test
   * @expectedException \Sk\SmartId\Exception\SessionTimeoutException
   */
  public function getUserTimeoutResponse_shouldThrowException()
  {
    $this->connector->responses[] = DummyData::createTimeoutSessionStatus();
    $this->poller->fetchFinalSessionStatus( '97f5058e-e308-4c83-ac14-7712b0eb9d86' );
  }

  /**
   * @test
   * @expectedException \Sk\SmartId\Exception\DocumentUnusableException
   */
  public function getDocumentUnusableResponse_shouldThrowException()
  {
    $this->connector->responses[] = DummyData::createDocumentUnusableSessionStatus();
    $this->poller->fetchFinalSessionStatus( '97f5058e-e308-4c83-ac14-7712b0eb9d86' );
  }

  /**
   * @test
   * @expectedException \Sk\SmartId\Exception\TechnicalErrorException
   */
  public function getUnknownEndResult_shouldThrowException()
  {
    $status = $this->createCompleteSessionStatus();
    $status->setResult( DummyData::createSessionResult( 'BLAH' ) );
    $this->connector->responses[] = $status;
    $this->poller->fetchFinalSessionStatus( '97f5058e-e308-4c83-ac14-7712b0eb9d86' );
  }

  /**
   * @test
   * @expectedException \Sk\SmartId\Exception\TechnicalErrorException
   */
  public function getMissingEndResult_shouldThrowException()
  {
    $status = $this->createCompleteSessionStatus();
    $status->setResult( null );
    $this->connector->responses[] = $status;
    $this->poller->fetchFinalSessionStatus( '97f5058e-e308-4c83-ac14-7712b0eb9d86' );
  }

  /**
   * @param SessionStatus $status
   */
  private function assertCompleteStateReceived( $status )
  {
    $this->assertNotNull( $status );
    $this->assertEquals( SessionStatusCode::COMPLETE, $status->getState() );
  }

  /**
   * @return float
   */
  private function measurePollingDuration()
  {
    $startTime = microtime( true );
    $status = $this->poller->fetchFinalSessionStatus( '97f5058e-e308-4c83-ac14-7712b0eb9d86' );
    $endTime = microtime( true );
    $this->assertCompleteStateReceived( $status );
    return round( ( $endTime - $startTime ) * 1000 );
  }

  /**
   * @return SessionStatus
   */
  private function createCompleteSessionStatus()
  {
    $sessionStatus = new SessionStatus();
    $sessionStatus->setState( SessionStatusCode::COMPLETE );
    $sessionStatus->setResult( DummyData::createSessionEndResult() );
    return $sessionStatus;
  }

  /**
   * @return SessionStatus
   */
  private function createRunningSessionStatus()
  {
    $sessionStatus = new SessionStatus();
    $sessionStatus->setState( SessionStatusCode::RUNNING );
    return $sessionStatus;
  }

  /**
   * @param int $numberOfResponses
   */
  private function addMultipleRunningSessionResponses( $numberOfResponses )
  {
    for ( $i = 0; $i < $numberOfResponses; $i++ )
    {
      $this->connector->responses[] = $this->createRunningSessionStatus();
    }
  }
}