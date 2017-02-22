<?php
namespace Sk\SmartId\Tests\Api;

use PHPUnit\Framework\TestCase;
use Sk\SmartId\Api\Data\CertificateParser;
use Sk\SmartId\Api\Data\SmartIdAuthenticationResult;

class SmartIdAuthenticationResultTest extends TestCase
{
  /**
   * @test
   */
  public function getSignatureValueInBase64()
  {
    $authenticationResult = new SmartIdAuthenticationResult();
    $authenticationResult->setValueInBase64( 'SGVsbG8gU21hcnQtSUQgc2lnbmF0dXJlIQ==' );
    $this->assertEquals( 'SGVsbG8gU21hcnQtSUQgc2lnbmF0dXJlIQ==', $authenticationResult->getValueInBase64() );
  }

  /**
   * @test
   */
  public function getSignatureValueInBytes()
  {
    $authenticationResult = new SmartIdAuthenticationResult();
    $authenticationResult->setValueInBase64( 'VGVyZSBhbGxraXJpIQ==' );
    $this->assertEquals( 'Tere allkiri!', $authenticationResult->getValue() );
  }

  /**
   * @test
   * @expectedException \Sk\SmartId\Exception\TechnicalErrorException
   */
  public function incorrectBase64StringShouldThrowException()
  {
    $authenticationResult = new SmartIdAuthenticationResult();
    $authenticationResult->setValueInBase64( '!IsNotValidBase64Character' );
    $authenticationResult->getValue();
  }

  /**
   * @test
   */
  public function getCertificate()
  {
    $authenticationResult = new SmartIdAuthenticationResult();
    $authenticationResult->setCertificate( DummyData::CERTIFICATE );
    $this->assertEquals( CertificateParser::parseX509Certificate( DummyData::CERTIFICATE ), $authenticationResult->getParsedCertificate() );
  }
}