<?php
namespace Sk\SmartId\Tests\Api;

use PHPUnit\Framework\TestCase;
use Sk\SmartId\Api\Data\AuthenticationHash;
use Sk\SmartId\Api\Data\HashType;

class AuthenticationHashTest extends TestCase
{
  /**
   * @test
   */
  public function generated_signableData_withDefaultHashType_sha512()
  {
    $authenticationHash = AuthenticationHash::generateRandomHash( HashType::SHA512 );
    $this->assertEquals( HashType::SHA512, $authenticationHash->getHashType() );
    $this->assertEquals( base64_decode( $authenticationHash->calculateHashInBase64() ),
        $authenticationHash->calculateHash() );
  }

  /**
   * @test
   */
  public function generated_signableData_withDefaultHashType_sha384()
  {
    $authenticationHash = AuthenticationHash::generateRandomHash( HashType::SHA384 );
    $this->assertEquals( HashType::SHA384, $authenticationHash->getHashType() );
    $this->assertEquals( base64_decode( $authenticationHash->calculateHashInBase64() ),
        $authenticationHash->calculateHash() );
  }

  /**
   * @test
   */
  public function generated_signableData_withDefaultHashType_sha256()
  {
    $authenticationHash = AuthenticationHash::generateRandomHash( HashType::SHA256 );
    $this->assertEquals( HashType::SHA256, $authenticationHash->getHashType() );
    $this->assertEquals( base64_decode( $authenticationHash->calculateHashInBase64() ),
        $authenticationHash->calculateHash() );
  }
}