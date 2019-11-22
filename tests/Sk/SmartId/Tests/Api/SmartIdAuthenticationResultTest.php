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
