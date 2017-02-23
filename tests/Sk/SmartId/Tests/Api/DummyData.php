<?php
namespace Sk\SmartId\Tests\Api;

use Sk\SmartId\Api\Data\SessionEndResultCode;
use Sk\SmartId\Api\Data\SessionResult;
use Sk\SmartId\Api\Data\SessionStatus;
use Sk\SmartId\Api\Data\SessionStatusCode;

class DummyData
{
  const CERTIFICATE = "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";

  /**
   * @return SessionResult
   */
  public static function createSessionEndResult()
  {
    $result = self::createSessionResult( SessionEndResultCode::OK );
    $result->setDocumentNumber( 'PNOEE-31111111111' );
    return $result;
  }

  /**
   * @return SessionStatus
   */
  public static function createUserRefusedSessionStatus()
  {
    $status = self::createCompleteSessionStatus();
    $status->setResult( self::createSessionResult( SessionEndResultCode::USER_REFUSED ) );
    return $status;
  }

  /**
   * @return SessionStatus
   */
  public static function createTimeoutSessionStatus()
  {
    $status = self::createCompleteSessionStatus();
    $status->setResult( self::createSessionResult( SessionEndResultCode::TIMEOUT ) );
    return $status;
  }

  /**
   * @return SessionStatus
   */
  public static function createDocumentUnusableSessionStatus()
  {
    $status = self::createCompleteSessionStatus();
    $status->setResult( self::createSessionResult( SessionEndResultCode::DOCUMENT_UNUSABLE ) );
    return $status;
  }

  /**
   * @param string $endResult
   * @return SessionResult
   */
  public static function createSessionResult( $endResult )
  {
    $result = new SessionResult();
    $result->setEndResult( $endResult );
    return $result;
  }

  /**
   * @return SessionStatus
   */
  private static function createCompleteSessionStatus()
  {
    $status = new SessionStatus();
    $status->setState( SessionStatusCode::COMPLETE );
    return $status;
  }
}