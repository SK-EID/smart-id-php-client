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
use ReflectionClass;
use Sk\SmartId\Api\AuthenticationResponseValidator;
use Sk\SmartId\Api\Data\AuthenticationCertificate;
use Sk\SmartId\Api\Data\AuthenticationIdentity;
use Sk\SmartId\Api\Data\CertificateLevelCode;
use Sk\SmartId\Api\Data\CertificateParser;
use Sk\SmartId\Api\Data\SessionEndResultCode;
use Sk\SmartId\Api\Data\SmartIdAuthenticationResponse;
use Sk\SmartId\Api\Data\SmartIdAuthenticationResultError;
use Sk\SmartId\Tests\Setup;

class AuthenticationResponseValidatorTest extends TestCase
{
  const VALID_SIGNATURE_IN_BASE64 = 'Whg9fyCr7/Pp/QWSoOauHLOj+hH3Q144kpjg889zWzpo1eAxXp4fTE3MZ+LRKNn7KiBtkXd/BcKmok66yk+NMNc95hhMbTm87tWUhqYVbqJz5popYz+vkMbzYNtMsvUBVLlOYCJcJqyFwzcYAkcyfgk4iDt10nkG7a1ngIGgSLkblQMySPduI++H0j4IFNGEhAXM51dSEx9NtpNRs5zlklOd+ccdVR0uXdK8OmAOrhzG03b8/FfVrDP62l7FtJEGej1GUkKJn6gcjaOi/lnQiPw2qZWFrsPA4OABkAgQUtwZ2CKTnrlAP3A4qMvbb5GF015PWtboz8T854pw/xixpzkL6sg79xSOAminwQQFovrKKzWn0A2bSRjUz5hRiv9yqZ1DL77pyZIQ91YZIBiS/rVE6+/jmbxtVIJnrecK8OnnfZo+HQu3LnNHkU0yq7im/VGCrSxNw9aExRFA5RuTNyCzZuX9iJfRibPLq2WUAYUb/JMiTc6Bg57DH5OUB9jOeRnj+vsciCXuYMy1B+fHG9WgR5M2EbpKije7rp6goDcpmS4xa+y2lVujXTFBbnOgxSo0ZF+xgko6PLWliRacgXPVVM3NgyfGHgrzZf8ANBlEOCBa47LQyeBSRiBsmzFrmkIvHf+57oHZhN1Ac8AOcX9O5LXJ0hpZyHQvHQXwbJk=';
  const INVALID_SIGNATURE_IN_BASE64 = 'Xhg9fyCr7/Pp/QWSoOauHLOj+hH3Q144kpjg889zWzpo1eAxXp4fTE3MZ+LRKNn7KiBtkXd/BcKmok66yk+NMNc95hhMbTm87tWUhqYVbqJz5popYz+vkMbzYNtMsvUBVLlOYCJcJqyFwzcYAkcyfgk4iDt10nkG7a1ngIGgSLkblQMySPduI++H0j4IFNGEhAXM51dSEx9NtpNRs5zlklOd+ccdVR0uXdK8OmAOrhzG03b8/FfVrDP62l7FtJEGej1GUkKJn6gcjaOi/lnQiPw2qZWFrsPA4OABkAgQUtwZ2CKTnrlAP3A4qMvbb5GF015PWtboz8T854pw/xixpzkL6sg79xSOAminwQQFovrKKzWn0A2bSRjUz5hRiv9yqZ1DL77pyZIQ91YZIBiS/rVE6+/jmbxtVIJnrecK8OnnfZo+HQu3LnNHkU0yq7im/VGCrSxNw9aExRFA5RuTNyCzZuX9iJfRibPLq2WUAYUb/JMiTc6Bg57DH5OUB9jOeRnj+vsciCXuYMy1B+fHG9WgR5M2EbpKije7rp6goDcpmS4xa+y2lVujXTFBbnOgxSo0ZF+xgko6PLWliRacgXPVVM3NgyfGHgrzZf8ANBlEOCBa47LQyeBSRiBsmzFrmkIvHf+57oHZhN1Ac8AOcX9O5LXJ0hpZyHQvHQXwbJk=';

  /**
   * @var AuthenticationResponseValidator
   */
  private $validator;

  protected function setUp()
  {
    $this->validator = new AuthenticationResponseValidator( Setup::RESOURCES );
  }

  /**
   * @test
   * @throws \ReflectionException
   */
  public function validationReturnsValidAuthenticationResult()
  {
    $response = $this->createValidValidationResponse();
    $authenticationResult = $this->validator->validate( $response );

    $this->assertTrue( $authenticationResult->isValid() );
    $this->assertTrue( empty( $authenticationResult->getErrors() ) );
    $this->assertAuthenticationIdentityValid( $authenticationResult->getAuthenticationIdentity(), $response->getCertificateInstance() );
  }

  /**
   * @test
   * @throws \ReflectionException
   */
  public function validationReturnsValidAuthenticationResult_whenEndResultLowerCase()
  {
    $response = $this->createValidValidationResponse();
    $response->setEndResult( strtolower( SessionEndResultCode::OK ) );
    $authenticationResult = $this->validator->validate( $response );

    $this->assertTrue( $authenticationResult->isValid() );
    $this->assertTrue( empty( $authenticationResult->getErrors() ) );
    $this->assertAuthenticationIdentityValid( $authenticationResult->getAuthenticationIdentity(), $response->getCertificateInstance() );
  }

  /**
   * @test
   * @throws \ReflectionException
   */
  public function validationReturnsInvalidAuthenticationResult_whenEndResultNotOk()
  {
    $response = $this->createValidationResponseWithInvalidEndResult();
    $authenticationResult = $this->validator->validate( $response );

    $this->assertFalse( $authenticationResult->isValid() );
    $this->assertTrue( in_array( SmartIdAuthenticationResultError::INVALID_END_RESULT, $authenticationResult->getErrors() ) );
    $this->assertAuthenticationIdentityValid( $authenticationResult->getAuthenticationIdentity(), $response->getCertificateInstance() );
  }

  /**
   * @test
   * @throws \ReflectionException
   */
  public function validationReturnsInvalidAuthenticationResult_whenSignatureVerificationFails()
  {
    $response = $this->createValidationResponseWithInvalidSignature();
    $authenticationResult = $this->validator->validate( $response );

    $this->assertFalse( $authenticationResult->isValid() );
    $this->assertTrue( in_array( SmartIdAuthenticationResultError::SIGNATURE_VERIFICATION_FAILURE, $authenticationResult->getErrors() ) );
    $this->assertAuthenticationIdentityValid( $authenticationResult->getAuthenticationIdentity(), $response->getCertificateInstance() );
  }

  /**
   * @test
   * @throws \ReflectionException
   */
  public function validationReturnsInvalidAuthenticationResult_whenSignersCertNotTrusted()
  {
    $response = $this->createValidValidationResponse();

    $validator = new AuthenticationResponseValidator( Setup::RESOURCES );
    $validator->clearTrustedCACertificates();
    $tmpHandle = tmpfile();
    fwrite( $tmpHandle, CertificateParser::getPemCertificate( DummyData::CERTIFICATE ) );
    $tmpFileMetadata = stream_get_meta_data( $tmpHandle );
    $validator->addTrustedCACertificateLocation( $tmpFileMetadata[ 'uri' ] );
    $authenticationResult = $validator->validate( $response );
    fclose( $tmpHandle );

    $this->assertFalse( $authenticationResult->isValid() );
    $this->assertTrue( in_array( SmartIdAuthenticationResultError::CERTIFICATE_NOT_TRUSTED, $authenticationResult->getErrors() ) );
    $this->assertAuthenticationIdentityValid( $authenticationResult->getAuthenticationIdentity(), $response->getCertificateInstance() );
  }

  /**
   * @test
   * @throws \ReflectionException
   */
  public function validationReturnsValidAuthenticationResult_whenCertificateLevelHigherThanRequested()
  {
    $response = $this->createValidationResponseWithHigherCertificateLevelThanRequested();
    $authenticationResult = $this->validator->validate( $response );

    $this->assertTrue( $authenticationResult->isValid() );
    $this->assertTrue( empty( $authenticationResult->getErrors() ) );
    $this->assertAuthenticationIdentityValid( $authenticationResult->getAuthenticationIdentity(), $response->getCertificateInstance() );
  }

  /**
   * @test
   * @throws \ReflectionException
   */
  public function validationReturnsInvalidAuthenticationResult_whenCertificateLevelLowerThanRequested()
  {
    $response = $this->createValidationResponseWithLowerCertificateLevelThanRequested();
    $authenticationResult = $this->validator->validate( $response );

    $this->assertFalse( $authenticationResult->isValid() );
    $this->assertTrue( in_array( SmartIdAuthenticationResultError::CERTIFICATE_LEVEL_MISMATCH, $authenticationResult->getErrors() ) );
    $this->assertAuthenticationIdentityValid( $authenticationResult->getAuthenticationIdentity(), $response->getCertificateInstance() );
  }

  /**
   * @test
   * @throws \ReflectionException
   */
  public function withEmptyRequestedCertificateLevel_shouldPass()
  {
    $response = $this->createValidValidationResponse();
    $response->setRequestedCertificateLevel( '' );
    $authenticationResult = $this->validator->validate( $response );

    $this->assertTrue( $authenticationResult->isValid() );
    $this->assertTrue( empty( $authenticationResult->getErrors() ) );
  }

  /**
   * @test
   * @throws \ReflectionException
   */
  public function withNullRequestedCertificateLevel_shouldPass()
  {
    $response = $this->createValidValidationResponse();
    $response->setRequestedCertificateLevel( null );
    $authenticationResult = $this->validator->validate( $response );

    $this->assertTrue( $authenticationResult->isValid() );
    $this->assertTrue( empty( $authenticationResult->getErrors() ) );
  }

  /**
   * @test
   * @expectedException \Sk\SmartId\Exception\TechnicalErrorException
   * @throws \ReflectionException
   */
  public function whenCertificateIsNull_ThenThrowException()
  {
    $response = $this->createValidValidationResponse();
    $response->setCertificate( null );
    $this->validator->validate( $response );
  }

  /**
   * @test
   * @expectedException \Sk\SmartId\Exception\TechnicalErrorException
   * @throws \ReflectionException
   */
  public function whenSignatureIsEmpty_ThenThrowException()
  {
    $response = $this->createValidValidationResponse();
    $response->setValueInBase64( '' );
    $this->validator->validate( $response );
  }

  /**
   * @return SmartIdAuthenticationResponse
   */
  private function createValidValidationResponse()
  {
    return $this->createValidationResponse( SessionEndResultCode::OK, self::VALID_SIGNATURE_IN_BASE64, CertificateLevelCode::QUALIFIED, CertificateLevelCode::QUALIFIED );
  }

  /**
   * @return SmartIdAuthenticationResponse
   */
  private function createValidationResponseWithInvalidEndResult()
  {
    return $this->createValidationResponse( 'NOT OK', self::VALID_SIGNATURE_IN_BASE64, CertificateLevelCode::QUALIFIED, CertificateLevelCode::QUALIFIED );
  }

  /**
   * @return SmartIdAuthenticationResponse
   */
  private function createValidationResponseWithInvalidSignature()
  {
    return $this->createValidationResponse( SessionEndResultCode::OK, self::INVALID_SIGNATURE_IN_BASE64, CertificateLevelCode::QUALIFIED, CertificateLevelCode::QUALIFIED );
  }

  private function createValidationResponseWithLowerCertificateLevelThanRequested()
  {
    return $this->createValidationResponse( SessionEndResultCode::OK, self::VALID_SIGNATURE_IN_BASE64, CertificateLevelCode::ADVANCED, CertificateLevelCode::QUALIFIED );
  }

  /**
   * @return SmartIdAuthenticationResponse
   */
  private function createValidationResponseWithHigherCertificateLevelThanRequested()
  {
    return $this->createValidationResponse( SessionEndResultCode::OK, self::VALID_SIGNATURE_IN_BASE64, CertificateLevelCode::QUALIFIED, CertificateLevelCode::ADVANCED );
  }

  /**
   * @param string $endResult
   * @param string $signatureInBase64
   * @param string $certificateLevel
   * @param string $requestedCertificateLevel
   * @return SmartIdAuthenticationResponse
   */
  private function createValidationResponse( $endResult, $signatureInBase64, $certificateLevel,
                                             $requestedCertificateLevel )
  {
    $authenticationResponse = new SmartIdAuthenticationResponse();
    $authenticationResponse->setEndResult( $endResult )
        ->setValueInBase64( $signatureInBase64 )
        ->setCertificate( DummyData::CERTIFICATE )
        ->setSignedData( 'Hello World!' )
        ->setCertificateLevel( $certificateLevel )
        ->setRequestedCertificateLevel( $requestedCertificateLevel );
    return $authenticationResponse;
  }

  /**
   * @param AuthenticationIdentity $authenticationIdentity
   * @param AuthenticationCertificate $certificate
   * @throws \ReflectionException
   */
  private function assertAuthenticationIdentityValid( AuthenticationIdentity $authenticationIdentity,
                                                      AuthenticationCertificate $certificate )
  {
    $subject = $certificate->getSubject();
    $subjectReflection = new ReflectionClass( $subject );
    foreach ( $subjectReflection->getProperties() as $property )
    {
      $property->setAccessible( true );
      if ( strcasecmp( $property->getName(), 'GN' ) === 0 )
      {
        $this->assertEquals( $property->getValue( $subject ), $authenticationIdentity->getGivenName() );
      }
      elseif ( strcasecmp( $property->getName(), 'SN' ) === 0 )
      {
        $this->assertEquals( $property->getValue( $subject ), $authenticationIdentity->getSurName() );
      }
      elseif ( strcasecmp( $property->getName(), 'SERIALNUMBER' ) === 0 )
      {
        $this->assertEquals( $property->getValue( $subject ), $authenticationIdentity->getIdentityCode() );
      }
      elseif ( strcasecmp( $property->getName(), 'C' ) === 0 )
      {
        $this->assertEquals( $property->getValue( $subject ), $authenticationIdentity->getCountry() );
      }
    }
  }
}
