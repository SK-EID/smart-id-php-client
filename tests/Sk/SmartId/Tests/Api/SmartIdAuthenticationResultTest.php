<?php
namespace Sk\SmartId\Tests\Api;

use PHPUnit\Framework\TestCase;
use Sk\SmartId\Api\Data\CertificateParser;
use Sk\SmartId\Api\Data\SmartIdAuthenticationResponse;

class SmartIdAuthenticationResultTest extends TestCase
{
  /**
   * @test
   */
  public function getSignatureValueInBase64()
  {
    $authenticationResponse = new SmartIdAuthenticationResponse();
    $authenticationResponse->setValueInBase64( 'SGVsbG8gU21hcnQtSUQgc2lnbmF0dXJlIQ==' );
    $this->assertEquals( 'SGVsbG8gU21hcnQtSUQgc2lnbmF0dXJlIQ==', $authenticationResponse->getValueInBase64() );
  }

  /**
   * @test
   */
  public function getSignatureValueInBytes()
  {
    $authenticationResponse = new SmartIdAuthenticationResponse();
    $authenticationResponse->setValueInBase64( 'VGVyZSBhbGxraXJpIQ==' );
    $this->assertEquals( 'Tere allkiri!', $authenticationResponse->getValue() );
  }

  /**
   * @test
   * @expectedException \Sk\SmartId\Exception\TechnicalErrorException
   */
  public function incorrectBase64StringShouldThrowException()
  {
    $authenticationResponse = new SmartIdAuthenticationResponse();
    $authenticationResponse->setValueInBase64( '!IsNotValidBase64Character' );
    $authenticationResponse->getValue();
  }

  /**
   * @test
   */
  public function getCertificate()
  {
    $authenticationResponse = new SmartIdAuthenticationResponse();
    $authenticationResponse->setCertificate( DummyData::CERTIFICATE );
    $this->assertEquals( CertificateParser::parseX509Certificate( DummyData::CERTIFICATE ),
        $authenticationResponse->getParsedCertificate() );
  }
}