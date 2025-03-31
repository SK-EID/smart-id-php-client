<?php

namespace Sk\SmartId\Tests\Util;

use PHPUnit\Framework\TestCase;
use Sk\SmartId\Api\AuthenticationResponseValidator;
use Sk\SmartId\Api\Data\AuthenticationCertificate;
use Sk\SmartId\Api\Data\CertificateParser;
use Sk\SmartId\Exception\UnprocessableSmartIdResponseException;
use Sk\SmartId\Tests\Api\AuthenticationResponseValidatorTest;
use Sk\SmartId\Tests\Setup;
use Sk\SmartId\Util\NationalIdentityNumberUtil;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNull;

class NationalIdentityNumberUtilTest extends TestCase
{

  /**
   * @test
   */
  public function testGetDateOfBirthFromIdCode_estonianIdCode_returns()
  {

    $parsedCertificate = CertificateParser::parseX509Certificate(AuthenticationResponseValidatorTest::AUTH_CERTIFICATE_EE);
    $eeCertificate = new AuthenticationCertificate($parsedCertificate);

    $authenticationResponseValidator = new AuthenticationResponseValidator(Setup::RESOURCES);
    $authenticationIdentity = $authenticationResponseValidator->constructAuthenticationIdentity($eeCertificate, AuthenticationResponseValidatorTest::AUTH_CERTIFICATE_EE);

    $nationalIdentityNumberUtil = new NationalIdentityNumberUtil();
    $dateOfBirth = $nationalIdentityNumberUtil->getDateOfBirth($authenticationIdentity);

    assertEquals("1801-01-01", $dateOfBirth->format('Y-m-d'));
  }

  /**
   * @test
   */
  public function getDateOfBirthFromIdCode_latvianIdCode_returns()
  {
    $parsedCertificate = CertificateParser::parseX509Certificate(AuthenticationResponseValidatorTest::AUTH_CERTIFICATE_LV_DOB_03_APRIL_1903);
    $lvCertificate = new AuthenticationCertificate($parsedCertificate);

    $authenticationResponseValidator = new AuthenticationResponseValidator(Setup::RESOURCES);
    $authenticationIdentity = $authenticationResponseValidator->constructAuthenticationIdentity($lvCertificate, AuthenticationResponseValidatorTest::AUTH_CERTIFICATE_LV_DOB_03_APRIL_1903);

    $nationalIdentityNumberUtil = new NationalIdentityNumberUtil();
    $dateOfBirth = $nationalIdentityNumberUtil->getDateOfBirth($authenticationIdentity);

    assertEquals("1903-04-03", $dateOfBirth->format('Y-m-d'));
    assertEquals("030403-10075", $authenticationIdentity->getIdentityNumber());
  }

  /**
   * @test
   */
  public function getDateOfBirthFromIdCode_newTypeOfLatvianIdCode_returns()
  {
    $parsedCertificate = CertificateParser::parseX509Certificate(AuthenticationResponseValidatorTest::AUTH_CERTIFICATE_LV_NEW_ID_CODE);
    $lvCertificate = new AuthenticationCertificate($parsedCertificate);

    $authenticationResponseValidator = new AuthenticationResponseValidator(Setup::RESOURCES);
    $authenticationIdentity = $authenticationResponseValidator->constructAuthenticationIdentity($lvCertificate, AuthenticationResponseValidatorTest::AUTH_CERTIFICATE_LV_NEW_ID_CODE);

    $nationalIdentityNumberUtil = new NationalIdentityNumberUtil();
    $dateOfBirthFromNationalIdNumber = $nationalIdentityNumberUtil->getDateOfBirth($authenticationIdentity);

    assertNull($dateOfBirthFromNationalIdNumber);
    assertEquals("1903-03-03", $authenticationIdentity->getDateOfBirth()->format('Y-m-d'));
    assertEquals("329999-99901", $authenticationIdentity->getIdentityNumber());
  }

  /**
   * @test
   */
  public function getDateOfBirthFromIdCode_lithuanianIdCode_returns()
  {
    $parsedCertificate = CertificateParser::parseX509Certificate(AuthenticationResponseValidatorTest::AUTH_CERTIFICATE_LT);
    $ltCertificate = new AuthenticationCertificate($parsedCertificate);

    $authenticationResponseValidator = new AuthenticationResponseValidator(Setup::RESOURCES);
    $authenticationIdentity = $authenticationResponseValidator->constructAuthenticationIdentity($ltCertificate, AuthenticationResponseValidatorTest::AUTH_CERTIFICATE_LT);

    $nationalIdentityNumberUtil = new NationalIdentityNumberUtil();
    $dateOfBirth = $nationalIdentityNumberUtil->getDateOfBirth($authenticationIdentity);

    assertEquals("1960-09-06", $dateOfBirth->format('Y-m-d'));
  }

  /**
   * @test
   */
  public function getDateOfBirthFromIdCode_belgianIdCode_returns()
  {
    $parsedCertificate = CertificateParser::parseX509Certificate(AuthenticationResponseValidatorTest::AUTH_CERTIFICATE_BE);
    $ltCertificate = new AuthenticationCertificate($parsedCertificate);

    $authenticationResponseValidator = new AuthenticationResponseValidator(Setup::RESOURCES);
    $authenticationIdentity = $authenticationResponseValidator->constructAuthenticationIdentity($ltCertificate, AuthenticationResponseValidatorTest::AUTH_CERTIFICATE_BE);

    $nationalIdentityNumberUtil = new NationalIdentityNumberUtil();
    $dateOfBirth = $nationalIdentityNumberUtil->getDateOfBirth($authenticationIdentity);

    assertNull($dateOfBirth);
  }

  /**
   * @test
   */
  public function parseLvDateOfBirth_withoutDateOfBirth_returnsNull()
  {
    $nationalIdentityNumberUtil = new NationalIdentityNumberUtil();
    $dateOfBirth = $nationalIdentityNumberUtil->parseLvDateOfBirth("321205-1234");

    assertNull($dateOfBirth);
  }

  /**
   * @test
   */
  public function parseLvDateOfBirth_21century()
  {
    $nationalIdentityNumberUtil = new NationalIdentityNumberUtil();
    $dateOfBirth = $nationalIdentityNumberUtil->parseLvDateOfBirth("131205-2234");

    assertEquals("2005-12-13", $dateOfBirth->format('Y-m-d'));
  }

  /**
   * @test
   */
  public function parseLvDateOfBirth_20century()
  {
    $nationalIdentityNumberUtil = new NationalIdentityNumberUtil();
    $dateOfBirth = $nationalIdentityNumberUtil->parseLvDateOfBirth("131265-1234");

    assertEquals("1965-12-13", $dateOfBirth->format('Y-m-d'));
  }

  /**
   * @test
   */
  public function parseLvDateOfBirth_19century()
  {
    $nationalIdentityNumberUtil = new NationalIdentityNumberUtil();
    $dateOfBirth = $nationalIdentityNumberUtil->parseLvDateOfBirth("131265-0234");

    assertEquals("1865-12-13", $dateOfBirth->format('Y-m-d'));
  }

  /**
   * @test
   */
  public function parseLvDateOfBirth_invalidMonth_throwsException()
  {
    $this->expectException(UnprocessableSmartIdResponseException::class);
    $this->expectExceptionMessage('Unable get birthdate from Latvian personal code 131365-1234');

    $nationalIdentityNumberUtil = new NationalIdentityNumberUtil();
    $dateOfBirth = $nationalIdentityNumberUtil->parseLvDateOfBirth("131365-1234");
    assertNull($dateOfBirth);
  }

  /**
   * @test
   */
  public function parseLvDateOfBirth_invalidIdCode_throwsException()
  {
    $this->expectException(UnprocessableSmartIdResponseException::class);
    $this->expectExceptionMessage('Unable get birthdate from Latvian personal code 331265-0234');

    $nationalIdentityNumberUtil = new NationalIdentityNumberUtil();
    $dateOfBirth = $nationalIdentityNumberUtil->parseLvDateOfBirth("331265-0234");
    assertNull($dateOfBirth);
  }

}