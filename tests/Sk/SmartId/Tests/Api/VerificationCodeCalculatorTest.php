<?php
namespace Sk\SmartId\Tests\Api;

use PHPUnit\Framework\TestCase;
use Sk\SmartId\Api\Data\DigestCalculator;
use Sk\SmartId\Api\Data\HashType;
use Sk\SmartId\Api\Data\VerificationCodeCalculator;

class VerificationCodeCalculatorTest extends TestCase
{
  /**
   * @test
   */
  public function calculateCorrectVerificationCode()
  {
    $this->assertVerificationCode( '7712', 'Hello World!' );
    $this->assertVerificationCode( '4612', 'Hedgehogs – why can\'t they just share the hedge?' );
    $this->assertVerificationCode( '7782', 'Go ahead, make my day.' );
    $this->assertVerificationCode( '1464', 'You\'re gonna need a bigger boat.' );
    $this->assertVerificationCode( '4240', 'Say \'hello\' to my little friend!' );
  }

  /**
   * @param string $verificationCode
   * @param string $dataString
   */
  private function assertVerificationCode( $verificationCode, $dataString )
  {
    $hash = DigestCalculator::calculateDigest( $dataString, HashType::SHA256 );
    $this->assertEquals( $verificationCode, VerificationCodeCalculator::calculate( $hash ) );
  }
}