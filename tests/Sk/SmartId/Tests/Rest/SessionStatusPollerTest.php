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
namespace Sk\SmartId\Tests\Rest;

use PHPUnit\Framework\TestCase;
use Sk\SmartId\Api\Data\SessionStatus;
use Sk\SmartId\Api\Data\SessionStatusCode;
use Sk\SmartId\Api\SessionStatusPoller;
use Sk\SmartId\Exception\DocumentUnusableException;
use Sk\SmartId\Exception\RequiredInteractionNotSupportedByAppException;
use Sk\SmartId\Exception\SessionTimeoutException;
use Sk\SmartId\Exception\TechnicalErrorException;
use Sk\SmartId\Exception\UserRefusedCertChoiceException;
use Sk\SmartId\Exception\UserRefusedConfirmationMessageException;
use Sk\SmartId\Exception\UserRefusedConfirmationMessageWithVcChoiceException;
use Sk\SmartId\Exception\UserRefusedDisplayTextAndPinException;
use Sk\SmartId\Exception\UserRefusedException;
use Sk\SmartId\Exception\UserRefusedVcChoiceException;
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

  protected function setUp() : void
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
  public function setResponseSocketTimeout()
  {
    $this->poller->setSessionStatusResponseSocketTimeoutMs( 2 * 60 * 1000 ); // 2 min
    $this->connector->responses[] = $this->createCompleteSessionStatus();
    $status = $this->poller->fetchFinalSessionStatus( '97f5058e-e308-4c83-ac14-7712b0eb9d86' );
    $this->assertCompleteStateReceived( $status );
    $this->assertTrue( $this->connector->requestUsed->isSessionStatusResponseSocketTimeoutSet() );
    $this->assertEquals( 2 * 60 * 1000, $this->connector->requestUsed->getSessionStatusResponseSocketTimeoutMs() );
  }

  /**
   * @test
   */
  public function responseSocketOpenTimeShouldNotBeSetByDefault()
  {
    $this->connector->responses[] = $this->createCompleteSessionStatus();
    $status = $this->poller->fetchFinalSessionStatus( '97f5058e-e308-4c83-ac14-7712b0eb9d86' );
    $this->assertCompleteStateReceived( $status );
    $this->assertFalse( $this->connector->requestUsed->isSessionStatusResponseSocketTimeoutSet() );
  }

  /**
   * @test
   *
   */
  public function getUserRefusedResponse_shouldThrowException()
  {
    $this->expectException(UserRefusedException::class);
    $this->connector->responses[] = DummyData::createUserRefusedSessionStatus();
    $this->poller->fetchFinalSessionStatus( '97f5058e-e308-4c83-ac14-7712b0eb9d86' );
  }

  /**
   * @test
   *
   */
  public function getUserTimeoutResponse_shouldThrowException()
  {
    $this->expectException(SessionTimeoutException::class);
    $this->connector->responses[] = DummyData::createTimeoutSessionStatus();
    $this->poller->fetchFinalSessionStatus( '97f5058e-e308-4c83-ac14-7712b0eb9d86' );
  }

  /**
   * @test
   *
   */
  public function getDocumentUnusableResponse_shouldThrowException()
  {
    $this->expectException(DocumentUnusableException::class);
    $this->connector->responses[] = DummyData::createDocumentUnusableSessionStatus();
    $this->poller->fetchFinalSessionStatus( '97f5058e-e308-4c83-ac14-7712b0eb9d86' );
  }

    /**
     * @test
     */
    public function getRequiredInteractionNotSupportedByAppResponse_shouldThrowException()
    {
        $this->expectException(RequiredInteractionNotSupportedByAppException::class);
        $this->connector->responses[] = DummyData::createRequiredInteractionNotSupportedByTheAppSessionStatus();
        $this->poller->fetchFinalSessionStatus( '97f5058e-e308-4c83-ac14-7712b0eb9d86' );
    }

    /**
     * @test
     */
    public function getUserRefusedDisplayTextAndPinResponse_shouldThrowException()
    {
        $this->expectException(UserRefusedDisplayTextAndPinException::class);
        $this->connector->responses[] = DummyData::createUserRefusedDisplayTextAndPinSessionStatus();
        $this->poller->fetchFinalSessionStatus( '97f5058e-e308-4c83-ac14-7712b0eb9d86' );
    }

    /**
     * @test
     */
    public function getUserRefusedVcChoiceResponse_shouldThrowException()
    {
        $this->expectException(UserRefusedVcChoiceException::class);
        $this->connector->responses[] = DummyData::createUserRefusedVcChoiceSessionStatus();
        $this->poller->fetchFinalSessionStatus( '97f5058e-e308-4c83-ac14-7712b0eb9d86' );
    }

    /**
     * @test
     */
    public function getUserRefusedConfirmationMessageResponse_shouldThrowException()
    {
        $this->expectException(UserRefusedConfirmationMessageException::class);
        $this->connector->responses[] = DummyData::createUserRefusedConfirmationMessageSessionStatus();
        $this->poller->fetchFinalSessionStatus( '97f5058e-e308-4c83-ac14-7712b0eb9d86' );
    }

    /**
     * @test
     */
    public function getUserRefusedConfirmationMessageWithVcChoiceResponse_shouldThrowException()
    {
        $this->expectException(UserRefusedConfirmationMessageWithVcChoiceException::class);
        $this->connector->responses[] = DummyData::createUserRefusedConfirmationMessageWithVcChoiceSessionStatus();
        $this->poller->fetchFinalSessionStatus( '97f5058e-e308-4c83-ac14-7712b0eb9d86' );
    }

    /**
     * @test
     */
    public function getUserRefusedCertChoiceResponse_shouldThrowException()
    {
        $this->expectException(UserRefusedCertChoiceException::class);
        $this->connector->responses[] = DummyData::createUserRefusedCertChoiceeSessionStatus();
        $this->poller->fetchFinalSessionStatus( '97f5058e-e308-4c83-ac14-7712b0eb9d86' );
    }

  /**
   * @test
   */
  public function getUnknownEndResult_shouldThrowException()
  {
    $this->expectException(TechnicalErrorException::class);
    $status = $this->createCompleteSessionStatus();
    $status->setResult( DummyData::createSessionResult( 'BLAH' ) );
    $this->connector->responses[] = $status;
    $this->poller->fetchFinalSessionStatus( '97f5058e-e308-4c83-ac14-7712b0eb9d86' );
  }

  /**
   * @test
   *
   */
  public function getMissingEndResult_shouldThrowException()
  {
    $this->expectException(TechnicalErrorException::class);
    $status = $this->createCompleteSessionStatus();
    $status->setResult( null );
    $this->connector->responses[] = $status;
    $this->poller->fetchFinalSessionStatus( '97f5058e-e308-4c83-ac14-7712b0eb9d86' );
  }

  /**
   * @param SessionStatus $status
   */
  private function assertCompleteStateReceived(SessionStatus $status )
  {
    $this->assertNotNull( $status );
    $this->assertEquals( SessionStatusCode::COMPLETE, $status->getState() );
  }

  /**
   * @return float
   */
  private function measurePollingDuration(): float
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
  private function createCompleteSessionStatus(): SessionStatus
  {
    $sessionStatus = new SessionStatus();
    $sessionStatus->setState( SessionStatusCode::COMPLETE );
    $sessionStatus->setResult( DummyData::createSessionEndResult() );
    return $sessionStatus;
  }

  /**
   * @return SessionStatus
   */
  private function createRunningSessionStatus(): SessionStatus
  {
    $sessionStatus = new SessionStatus();
    $sessionStatus->setState( SessionStatusCode::RUNNING );
    return $sessionStatus;
  }

  /**
   * @param int $numberOfResponses
   */
  private function addMultipleRunningSessionResponses(int $numberOfResponses )
  {
    for ( $i = 0; $i < $numberOfResponses; $i++ )
    {
      $this->connector->responses[] = $this->createRunningSessionStatus();
    }
  }
}
