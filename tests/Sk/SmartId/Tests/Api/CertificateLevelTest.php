<?php
namespace Sk\SmartId\Tests\Api;

use PHPUnit\Framework\TestCase;
use Sk\SmartId\Api\CertificateLevel;
use Sk\SmartId\Api\Data\CertificateLevelCode;

class CertificateLevelTest extends TestCase
{
  /**
   * @test
   */
  public function testBothCertificateLevelsQualified()
  {
    $certificateLevel = new CertificateLevel( CertificateLevelCode::QUALIFIED );
    $this->assertTrue( $certificateLevel->isEqualOrAbove( CertificateLevelCode::QUALIFIED ) );
  }

  /**
   * @test
   */
  public function testBothCertificateLevelsAdvanced()
  {
    $certificateLevel = new CertificateLevel( CertificateLevelCode::ADVANCED );
    $this->assertTrue( $certificateLevel->isEqualOrAbove( CertificateLevelCode::ADVANCED ) );
  }

  /**
   * @test
   */
  public function testFirstCertificateLevelHigher()
  {
    $certificateLevel = new CertificateLevel( CertificateLevelCode::QUALIFIED );
    $this->assertTrue( $certificateLevel->isEqualOrAbove( CertificateLevelCode::ADVANCED ) );
  }

  /**
   * @test
   */
  public function testFirstCertificateLevelLower()
  {
    $certificateLevel = new CertificateLevel( CertificateLevelCode::ADVANCED );
    $this->assertFalse( $certificateLevel->isEqualOrAbove( CertificateLevelCode::QUALIFIED ) );
  }

  /**
   * @test
   */
  public function testFirstCertLevelUnknown()
  {
    $certificateLevel = new CertificateLevel( 'SOME UNKNOWN LEVEL' );
    $this->assertFalse( $certificateLevel->isEqualOrAbove( CertificateLevelCode::ADVANCED ) );
  }

  /**
   * @test
   */
  public function testSecondCertLevelUnknown()
  {
    $certificateLevel = new CertificateLevel( CertificateLevelCode::ADVANCED );
    $this->assertFalse( $certificateLevel->isEqualOrAbove( 'SOME UNKNOWN LEVEL' ) );
  }

  /**
   * @test
   */
  public function testBothCertLevelUnknown()
  {
    $certificateLevel = new CertificateLevel( 'SOME UNKNOWN LEVEL' );
    $this->assertTrue( $certificateLevel->isEqualOrAbove( 'SOME UNKNOWN LEVEL' ) );
  }
}