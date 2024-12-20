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
namespace Sk\SmartId\Tests\Api;

use Sk\SmartId\Api\Data\SessionEndResultCode;
use Sk\SmartId\Api\Data\SessionResult;
use Sk\SmartId\Api\Data\SessionStatus;
use Sk\SmartId\Api\Data\SessionStatusCode;

class DummyData
{
  // valid until: 2030-12-18
  const CERTIFICATE = "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";

  // valid until: 2024-03-12
  const CERTIFICATE_WITHOUT_DOB_FIELD = "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";

  const CERTIFICATE_LEVEL = "ADVANCED";
  const DEMO_HOST_URL = "https://sid.demo.sk.ee/smart-id-rp/v2/";
  const TEST_URL = self::DEMO_HOST_URL;

  const DEMO_RELYING_PARTY_UUID = "55555555-0000-0000-0000-000000000000";
  const DEMO_RELYING_PARTY_NAME = "DEMO PHP client";


  const VALID_SEMANTICS_IDENTIFIER = "PNOLT-30303039914";
  const VALID_DOCUMENT_NUMBER = "PNOLT-30303039914-MOCK-Q";

  const SIGNABLE_TEXT = "hashvalueinbase64";

  const NETWORK_INTERFACE = "docker0"; // network interface in machine. for example "docker0", "en7", "eth0", "127.0.0.1"

  /**
   * @return SessionResult
   */
  public static function createSessionEndResult(): SessionResult
  {
    $result = self::createSessionResult( SessionEndResultCode::OK );
    $result->setDocumentNumber( 'PNOEE-31111111111' );
    return $result;
  }

  /**
   * @return SessionStatus
   */
  public static function createUserRefusedSessionStatus(): SessionStatus
  {
    $status = self::createCompleteSessionStatus();
    $status->setResult( self::createSessionResult( SessionEndResultCode::USER_REFUSED ) );
    return $status;
  }

  /**
   * @return SessionStatus
   */
  public static function createTimeoutSessionStatus(): SessionStatus
  {
    $status = self::createCompleteSessionStatus();
    $status->setResult( self::createSessionResult( SessionEndResultCode::TIMEOUT ) );
    return $status;
  }

  /**
   * @return SessionStatus
   */
  public static function createDocumentUnusableSessionStatus(): SessionStatus
  {
    $status = self::createCompleteSessionStatus();
    $status->setResult( self::createSessionResult( SessionEndResultCode::DOCUMENT_UNUSABLE ) );
    return $status;
  }

    /**
     * @return SessionStatus
     */
    public static function createRequiredInteractionNotSupportedByTheAppSessionStatus(): SessionStatus
    {
        $status = self::createCompleteSessionStatus();
        $status->setResult( self::createSessionResult( SessionEndResultCode::REQUIRED_INTERACTION_NOT_SUPPORTED_BY_APP ) );
        return $status;
    }

    /**
     * @return SessionStatus
     */
    public static function createUserRefusedDisplayTextAndPinSessionStatus(): SessionStatus
    {
        $status = self::createCompleteSessionStatus();
        $status->setResult( self::createSessionResult( SessionEndResultCode::USER_REFUSED_DISPLAYTEXTANDPIN ) );
        return $status;
    }

    /**
     * @return SessionStatus
     */
    public static function createUserRefusedVcChoiceSessionStatus(): SessionStatus
    {
        $status = self::createCompleteSessionStatus();
        $status->setResult( self::createSessionResult( SessionEndResultCode::USER_REFUSED_VC_CHOICE ) );
        return $status;
    }

    /**
     * @return SessionStatus
     */
    public static function createUserRefusedConfirmationMessageSessionStatus(): SessionStatus
    {
        $status = self::createCompleteSessionStatus();
        $status->setResult( self::createSessionResult( SessionEndResultCode::USER_REFUSED_CONFIRMATIONMESSAGE ) );
        return $status;
    }

    /**
     * @return SessionStatus
     */
    public static function createUserRefusedConfirmationMessageWithVcChoiceSessionStatus(): SessionStatus
    {
        $status = self::createCompleteSessionStatus();
        $status->setResult( self::createSessionResult( SessionEndResultCode::USER_REFUSED_CONFIRMATIONMESSAGE_WITH_VC_CHOICE ) );
        return $status;
    }

    /**
     * @return SessionStatus
     */
    public static function createUserRefusedCertChoiceeSessionStatus(): SessionStatus
    {
        $status = self::createCompleteSessionStatus();
        $status->setResult( self::createSessionResult( SessionEndResultCode::USER_REFUSED_CERT_CHOICE ) );
        return $status;
    }

  /**
   * @param string $endResult
   * @return SessionResult
   */
  public static function createSessionResult(string $endResult ): SessionResult
  {
    $result = new SessionResult();
    $result->setEndResult( $endResult );
    return $result;
  }

  /**
   * @return SessionStatus
   */
  private static function createCompleteSessionStatus(): SessionStatus
  {
    $status = new SessionStatus();
    $status->setState( SessionStatusCode::COMPLETE );
    return $status;
  }
}
