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
use Sk\SmartId\Exception\TechnicalErrorException;
use Sk\SmartId\Tests\Setup;
use function PHPUnit\Framework\assertEquals;

class AuthenticationResponseValidatorTest extends TestCase
{
  const VALID_SIGNATURE_IN_BASE64 = 'Z+ub8dA4XCywamoiEOt7rUW8DJzzheQcmOyIszeyDt/+lqWGVOIM9K0dKhJsfPFy36pT8zQozLcPYfLBzOga3shSbXP+L+3gVyEEv9cAw9tUgzj7RPHlF+yZYzVITqzM4lYGq/1+1X0luenSJVUURttpOoCcbrLkOip27cFO477a3yyT10S4WWgE9B228rGOQv3/PETDxSamkK7uQVYiF0Zkhtdj5fi/hAjdn9GP/Yy61mOVRZv4vCWJbEaz2aO4q/us2JRMksJ6rjHbrgGH7n/8tjZF4i0tLZRreHKOboOi2iJV1NOhGaYstBOBVxCPeXhLAoocRWw+e2YGxORVYZaqitZ7LrZzF7anytrNvCyoH2NDv9Ylmj0gaVF9P6fQ2naLzjOZpETehAxK4wms66K3GXVQ8huJn5w8O2o0drXsMv7rGqaKDdsYL1j+Xa4MWph0XJiDiO7i6GK04L1HjOyPK0qoA5n6QKTdUAcaqE2mwDEmz2YZ1pKVc1RI47GUrydcI0mVzkBg0D+6ir8Rh4aAk7Npe48Ni9X+K3xrYVmjoMkixNQO01p8/UenokNAuCOvXhrCs9tcdCucGZv2u50lP0bMFHXvdKXB83dXM5UuNh9lgAYifXxhgxd+REgHwTUcQnBKdvZDHZOTTSVXnZbdiDi+u++S/bxeswdX4fZ4ebaBaEOM+0obiKEE1KEN6rMDsHe9OfdI9RvbdCdf6XkjPFl00+WVZmmiiBKIW8yzMZ/q3D3FD1fNe7KjpWl9cJy4Jw6/q6BYDcGvFBlq8ebbiZ3PzJmfgB4BaAHTRdUr0d9VnMYPHEtyyNPFEH0GeEI/FbmRxXsfETm+vVX1nRnKomJVmbthxYj8aRGKPhzCOaGByG6mDiyw/UABENQHop8lR7+nDdBXRAeWgBA4s4lwJ2xx7UAmrj6ZxGKNwqTxnReG6J0uyCs2uiZOwoQtFtcXx6UoAMAc1wlvSDr+VV2OU75aWsMnCsqphcSOCIMp0JtXgctVYWegklHu8a93';
  const INVALID_SIGNATURE_IN_BASE64 = 'Xhg9fyCr7/Pp/QWSoOauHLOj+hH3Q144kpjg889zWzpo1eAxXp4fTE3MZ+LRKNn7KiBtkXd/BcKmok66yk+NMNc95hhMbTm87tWUhqYVbqJz5popYz+vkMbzYNtMsvUBVLlOYCJcJqyFwzcYAkcyfgk4iDt10nkG7a1ngIGgSLkblQMySPduI++H0j4IFNGEhAXM51dSEx9NtpNRs5zlklOd+ccdVR0uXdK8OmAOrhzG03b8/FfVrDP62l7FtJEGej1GUkKJn6gcjaOi/lnQiPw2qZWFrsPA4OABkAgQUtwZ2CKTnrlAP3A4qMvbb5GF015PWtboz8T854pw/xixpzkL6sg79xSOAminwQQFovrKKzWn0A2bSRjUz5hRiv9yqZ1DL77pyZIQ91YZIBiS/rVE6+/jmbxtVIJnrecK8OnnfZo+HQu3LnNHkU0yq7im/VGCrSxNw9aExRFA5RuTNyCzZuX9iJfRibPLq2WUAYUb/JMiTc6Bg57DH5OUB9jOeRnj+vsciCXuYMy1B+fHG9WgR5M2EbpKije7rp6goDcpmS4xa+y2lVujXTFBbnOgxSo0ZF+xgko6PLWliRacgXPVVM3NgyfGHgrzZf8ANBlEOCBa47LQyeBSRiBsmzFrmkIvHf+57oHZhN1Ac8AOcX9O5LXJ0hpZyHQvHQXwbJk=';

  const AUTH_CERTIFICATE_EE = "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";
  const AUTH_CERTIFICATE_LV_DOB_03_APRIL_1903 = "MIIIhTCCBm2gAwIBAgIQd8HszDVDiJBgRUH8bND/GzANBgkqhkiG9w0BAQsFADBoMQswCQYDVQQGEwJFRTEiMCAGA1UECgwZQVMgU2VydGlmaXRzZWVyaW1pc2tlc2t1czEXMBUGA1UEYQwOTlRSRUUtMTA3NDcwMTMxHDAaBgNVBAMME1RFU1Qgb2YgRUlELVNLIDIwMTYwHhcNMjEwMzA3MjExMzMyWhcNMjQwMzA3MjExMzMyWjCBgzELMAkGA1UEBhMCTFYxLzAtBgNVBAMMJlRFU1ROVU1CRVIsV1JPTkdfVkMsUE5PTFYtMDMwNDAzLTEwMDc1MRMwEQYDVQQEDApURVNUTlVNQkVSMREwDwYDVQQqDAhXUk9OR19WQzEbMBkGA1UEBRMSUE5PTFYtMDMwNDAzLTEwMDc1MIIDIjANBgkqhkiG9w0BAQEFAAOCAw8AMIIDCgKCAwEAjC6yZx8T1M56IHYCOsOnYhZwtaPP/z4+2A8XDsRz03qj8+80iHxRI4A6+8tIZdEq58QDbpN+BHRE4RHhsdz7RVZJQ9Gxp3dGutJAjxSONBbwzCzmo9fyy+svVBIFZAUbKAZWI6PzDHIztkMJNRONb6DachdX3L0gIGGxFUlbL/DJIhRjAmOG8rJht/bCHwFv0uBrUAGSvJ3AHgokouvwREThM/gvKlijhaPXxACTpignu1jETYJieVC8JS6E2YU+1nca+TCMNa65/KNLjF4Pd+QchLQtJbxEPzsdnHIkwh5SVGegAxpVk/My/9WbL1v08PnivyCARu6/Bc+KX0SERg93+IMrKC+dbkiULMMOWxCXV1LjarFhS0FgQCzdueS96lpMrwfb2ctQRlhRIaP7yOh2IEoHP4diQgzvpVsIywH8oN+lrXtciR8ufhFhsklIRa21iO+PuTY6B+LVpAyZAQFEISUkXOqnzBopFd8OJqyu5z7S7V+axNSeHhyTIXG1Ys+HwGc+w/DBu5KhOONNgmNCeXF6d3ACuMFF6K07ghouBk5fC27Fsgl6D7u2niawgb5ouGXvHq4a756swJphZq63diHE+vBqQHCzdnneVVhiWCwc8bqtNf6ueZtv6hIgzPrFt707IrGbPQ7LvYGmNI/Me7567fzaBNEaykBw/YWqyDV1S3tFKIjKcD/5NGGBDqbHNK1r4Ozob5xJQHpptiYvreQNlPPeTc6aSChS1AK5LTbxrLxifZSh9TOO8IklXdNS6Q4b7th23KhNmU0QGuGva7/JHexfLUuknBr92b8ink4zeZsoe69SI2xW/ta/ANVl4FN2LhJqgyplskNkUCwFadplcKs3+m5gBggz7kh8cLhcaobfHRHh0ogz5kxM95smrk+tFm/oEKV7VkUT9A5ky8Fvei6MtqZ/SmrIiv4Sdlj71U8laGZmZtR7Kgrpu2KMlZROAZdcvvq/ASbhSVfoebUAj+knvds2wOnC9N8MZU8O46UkKwupiyr/KPexAgMBAAGjggINMIICCTAJBgNVHRMEAjAAMA4GA1UdDwEB/wQEAwIGQDBVBgNVHSAETjBMMD8GCisGAQQBzh8DEQIwMTAvBggrBgEFBQcCARYjaHR0cHM6Ly93d3cuc2suZWUvZW4vcmVwb3NpdG9yeS9DUFMwCQYHBACL7EABAjAdBgNVHQ4EFgQUCLo2Ioa+lsHpd4UfpJLRTrs2CjQwgaMGCCsGAQUFBwEDBIGWMIGTMAgGBgQAjkYBATAVBggrBgEFBQcLAjAJBgcEAIvsSQEBMBMGBgQAjkYBBjAJBgcEAI5GAQYBMFEGBgQAjkYBBTBHMEUWP2h0dHBzOi8vc2suZWUvZW4vcmVwb3NpdG9yeS9jb25kaXRpb25zLWZvci11c2Utb2YtY2VydGlmaWNhdGVzLxMCRU4wCAYGBACORgEEMB8GA1UdIwQYMBaAFK6w6uE2+CarpcwLZlX+Oh0CvxK0MHwGCCsGAQUFBwEBBHAwbjApBggrBgEFBQcwAYYdaHR0cDovL2FpYS5kZW1vLnNrLmVlL2VpZDIwMTYwQQYIKwYBBQUHMAKGNWh0dHA6Ly9zay5lZS91cGxvYWQvZmlsZXMvVEVTVF9vZl9FSUQtU0tfMjAxNi5kZXIuY3J0MDEGA1UdEQQqMCikJjAkMSIwIAYDVQQDDBlQTk9MVi0wMzA0MDMtMTAwNzUtWkg0TS1RMA0GCSqGSIb3DQEBCwUAA4ICAQDli94AjzgMUTdjyRzZpOUQg3CljwlMlAKm8jeVDBEL6iQiZuCjc+3BzTbBJU7S8Ye9JVheTaSRJm7HqsSWzm1CYPkJkP9xlqRD9aig57FDgL9MXCWNqUlUf2qtoYEUudW9JgR7eNuLfdOFnUEt4qJm3/F/+emIFnf7xWrS2yaMiRwliA3mJxffh33GRVsEO/w5W4LHpU1v/Pbkuu5hyUGw5IybV9odHTF+JnAPsElBjY9OhB8q+5iwAt++8Udvc1gS4vBIvJzRFrl8XA56AJjl061sm436imAYsy4J6QCz8bdu04tcSJyO+c/sDqDNHjXztFLR8TIqV/amkvP+acavSWULy2NxPDtmD4Pn3T3ycQfeT1HkwZGn3HogLbwqfBbLTWYzNjIfQZthox51IrCSDXbvL9AL3zllFGMcnnc6UkZ4k4+M3WsYD6cnpTl/YZ0R9spc8yQ+Vgj58Iq7yyzY/Uf1OkS0GCTBPtfToKmEXUFwKma/pcmsHx5aV7Pm2Lo+FiTrVw0lgB+t0qGlqT52j4H7KrvQi0xDuEapqbR3AAPZuiT8+S6Q9Oyq70kS0CG9vZ0f6q3Pz1DfCG8hUcjwzaf5McWMQLSdQK5RKkimDW71Ir2AmSTRNvm0A3IbhuEX2JVN0UGBhV5oIy8ypaC9/3XSnS4ZeQCF9WbA2IOmyw==";
  const AUTH_CERTIFICATE_LV_NEW_ID_CODE = "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";
  const AUTH_CERTIFICATE_LT = "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";
  const AUTH_CERTIFICATE_BE = "MIIGsjCCBjmgAwIBAgIQHZCIieijthTEJ3yVgNT60TAKBggqhkjOPQQDAzBxMSwwKgYDVQQDDCNURVNUIG9mIFNLIElEIFNvbHV0aW9ucyBFSUQtUSAyMDI0RTEXMBUGA1UEYQwOTlRSRUUtMTA3NDcwMTMxGzAZBgNVBAoMElNLIElEIFNvbHV0aW9ucyBBUzELMAkGA1UEBhMCRUUwHhcNMjQxMDE1MTY0NTEzWhcNMjcxMDE1MTY0NTEyWjBjMQswCQYDVQQGEwJCRTEWMBQGA1UEAwwNVEVTVE5VTUJFUixPSzETMBEGA1UEBAwKVEVTVE5VTUJFUjELMAkGA1UEKgwCT0sxGjAYBgNVBAUTEVBOT0JFLTA1MDQwNDAwMDMyMIIDITANBgkqhkiG9w0BAQEFAAOCAw4AMIIDCQKCAwBr4rfhBCrz7O8dQNcjI5hnTliyRolxrlg9fZnmQMiNZX8HzA9gjcVO+wfDJhwXrRFuA85WT4O5bk6ee8Ccl12hwOCsoMZxhWSbTjy8lTpyYI94xizRHO6bd0sn6q5SJ73DFqyLxgLarHWMgX5EH86ocpEtzn7w7Hz6R0TwMxo4RpCcrPuqrY6YDn7yPHGgl+rZWwtmPWEGJ3SnERg14GnG0llg7VTzi43HIoPH+twLFR7dLmZunIZylSh9SyH2jEcB7UAAjrtSwFErzxiN9SwsVRQLUyFMlQEOB3nvc/uY7pnieMc7+13dL0M2uLUdO2JI6O3B4kPR+1jWvzzYkID6OR0qJUXYwo/TULvAVsL57wBoJJl2R8R33yedF+vHKJhU3mHqvNgwmC2wCwjkKppe4K4HWm1tMP5e4e+4G8l6suZESX+GnR81KD6iggljhcbrVf0UJHIC6TC8NMbaVatiNDI4RqmYwZskrofsg5bae59Yqdrpxr3pbtM3RZEna5bOJPY4NLWmLvKYnHpkUahoovgOu9B8Z5PWFWbjhsN0ceUdYaG+IHyGXeSH9AYeOlmaAZOaMK9dibImqE/mDJ1yD6ExIbM65989jpG/h3OOX+xu/ZfURpwN4BEIxn0TZpDQ5ZExtNTwRGpvAyRHjex3QRSCb1TMaWEvz28KMSfemONxVChEtWEP4b2kXhuv6N6kHB3cZ7qx+NkmNo3AmBXzxHud4SvkdOcnZr1APWaeWAGPCesqZvzu81b6n1uNQf0DcgJa3/2r+OMFSi31MPPTRz4xqex7WdSBXCIjIONIuq4BZ6VS2quNV78aR+u6diOxOwnyQiFC1R7NyWP/HkE1lkeYDjZiFvTBIuewcREapYiv1ZrxcCcRVDKL/SXC711s9e7eXcHL1eKCIBQnPzq2OZZrsT3EB+eRZ8skHQ4hBbvSq+fC937yvTjJv5jbCvWxLw3AN7drA4xX9kmmnh2xVHgOUfUavLn6i4QUGkvp5k4oUVslvbWLhQxdSidrnWMCAwEAAaOCAfUwggHxMAkGA1UdEwQCMAAwHwYDVR0jBBgwFoAUsCQXGYjjZvjNKFhle00U2JJmT2swcAYIKwYBBQUHAQEEZDBiMDMGCCsGAQUFBzAChidodHRwOi8vYy5zay5lZS9URVNUX0VJRC1RXzIwMjRFLmRlci5jcnQwKwYIKwYBBQUHMAGGH2h0dHA6Ly9haWEuZGVtby5zay5lZS9laWRxMjAyNGUwMAYDVR0RBCkwJ6QlMCMxITAfBgNVBAMMGFBOT0JFLTA1MDQwNDAwMDMyLU1PQ0stUTB4BgNVHSAEcTBvMGMGCSsGAQQBzh8RAjBWMFQGCCsGAQUFBwIBFkhodHRwczovL3d3dy5za2lkc29sdXRpb25zLmV1L3Jlc291cmNlcy9jZXJ0aWZpY2F0aW9uLXByYWN0aWNlLXN0YXRlbWVudC8wCAYGBACPegECMCgGA1UdCQQhMB8wHQYIKwYBBQUHCQExERgPMTkwNTA0MDQxMjAwMDBaMBYGA1UdJQQPMA0GCysGAQQBg+ZiBQcAMDQGA1UdHwQtMCswKaAnoCWGI2h0dHA6Ly9jLnNrLmVlL3Rlc3RfZWlkLXFfMjAyNGUuY3JsMB0GA1UdDgQWBBT6lq9sVwoDW6G0HOidAd52wGqo9zAOBgNVHQ8BAf8EBAMCB4AwCgYIKoZIzj0EAwMDZwAwZAIwaRKLn9O9v8TDcZIzhBUOMsSxSC79LJe/2gIdw4caN8rLWJLdWwNjvuC5pBALLDhdAjBX+tAu6Ri14k8S6b+3tjBvv7vyjy+7Jgv25oqxpaJEXXhy3P5C7aL0bYzbCCUfNMg=";

  /**
   * @var AuthenticationResponseValidator
   */
  private $validator;

  protected function setUp() : void
  {
    $this->validator = new AuthenticationResponseValidator( Setup::RESOURCES );
  }

  /**
   * @test
   */
  public function validationReturnsValidAuthenticationResult()
  {
    $response = $this->createValidValidationResponse();
    $authenticationResult = $this->validator->validate( $response );

    $this->assertTrue( $authenticationResult->isValid() );
    $this->assertEmpty($authenticationResult->getErrors());
    $this->assertAuthenticationIdentityValid( $authenticationResult->getAuthenticationIdentity(), $response->getCertificateInstance() );
  }

  /**
   * @test
   */
  public function validationReturnsValidAuthenticationResult_whenEndResultLowerCase()
  {
    $response = $this->createValidValidationResponse();
    $response->setEndResult( strtolower( SessionEndResultCode::OK ) );
    $authenticationResult = $this->validator->validate( $response );

    $this->assertTrue( $authenticationResult->isValid() );
    $this->assertEmpty($authenticationResult->getErrors());
    $this->assertAuthenticationIdentityValid( $authenticationResult->getAuthenticationIdentity(), $response->getCertificateInstance() );
  }

  /**
   * @test
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
   */
  public function validationReturnsValidAuthenticationResult_whenCertificateLevelHigherThanRequested()
  {
    $response = $this->createValidationResponseWithHigherCertificateLevelThanRequested();
    $authenticationResult = $this->validator->validate( $response );

    $this->assertTrue( $authenticationResult->isValid() );
    $this->assertEmpty($authenticationResult->getErrors());
    $this->assertAuthenticationIdentityValid( $authenticationResult->getAuthenticationIdentity(), $response->getCertificateInstance() );
  }

  /**
   * @test
   */
  public function constructAuthenticationIdentity()
  {
    $parsedCertificate = CertificateParser::parseX509Certificate(AuthenticationResponseValidatorTest::AUTH_CERTIFICATE_EE);
    $eeCertificate = new AuthenticationCertificate($parsedCertificate);

    $authenticationResponseValidator = new AuthenticationResponseValidator(Setup::RESOURCES);
    $authenticationIdentity = $authenticationResponseValidator->constructAuthenticationIdentity($eeCertificate, AuthenticationResponseValidatorTest::AUTH_CERTIFICATE_EE);

    assertEquals('PNOEE-10101010005', $authenticationIdentity->getIdentityCode());
    assertEquals('10101010005', $authenticationIdentity->getIdentityNumber());
    assertEquals('DEMO', $authenticationIdentity->getGivenName());
    assertEquals('SMART-ID', $authenticationIdentity->getSurName());
    assertEquals('EE', $authenticationIdentity->getCountry());
  }

  /**
   * @test
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
   */
  public function getDateOfBirth_latvianIdCodeThatDoesNotContainBirthdateInfo_dateOfBirthReturnedFromCertificateField()
  {
    $parsedCertificate = CertificateParser::parseX509Certificate(AuthenticationResponseValidatorTest::AUTH_CERTIFICATE_LV_NEW_ID_CODE);
    $eeCertificate = new AuthenticationCertificate($parsedCertificate);

    $authenticationResponseValidator = new AuthenticationResponseValidator(Setup::RESOURCES);
    $authenticationIdentity = $authenticationResponseValidator->constructAuthenticationIdentity($eeCertificate, AuthenticationResponseValidatorTest::AUTH_CERTIFICATE_LV_NEW_ID_CODE);

    $this->assertEquals('LV', $authenticationIdentity->getCountry());
    $this->assertEquals('329999-99901', $authenticationIdentity->getIdentityNumber());
    $this->assertEquals('1903-03-03', $authenticationIdentity->getDateOfBirth()->format('Y-m-d'));
  }

  /**
   * @test
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
   */
  public function whenCertificateIsNull_ThenThrowException()
  {
    $this->expectException(TechnicalErrorException::class);
    $response = $this->createValidValidationResponse();
    $response->setCertificate( null );
    $this->validator->validate( $response );
  }

  /**
   * @test
   */
  public function whenSignatureIsEmpty_ThenThrowException()
  {
    $this->expectException(TechnicalErrorException::class);
    $response = $this->createValidValidationResponse();
    $response->setValueInBase64( '' );
    $this->validator->validate( $response );
  }

  /**
   * @return SmartIdAuthenticationResponse
   */
  private function createValidValidationResponse(): SmartIdAuthenticationResponse
  {
    return $this->createValidationResponse( SessionEndResultCode::OK, self::VALID_SIGNATURE_IN_BASE64, CertificateLevelCode::QUALIFIED, CertificateLevelCode::QUALIFIED );
  }

  /**
   * @return SmartIdAuthenticationResponse
   */
  private function createValidationResponseWithInvalidEndResult(): SmartIdAuthenticationResponse
  {
    return $this->createValidationResponse( 'NOT OK', self::VALID_SIGNATURE_IN_BASE64, CertificateLevelCode::QUALIFIED, CertificateLevelCode::QUALIFIED );
  }

  /**
   * @return SmartIdAuthenticationResponse
   */
  private function createValidationResponseWithInvalidSignature(): SmartIdAuthenticationResponse
  {
    return $this->createValidationResponse( SessionEndResultCode::OK, self::INVALID_SIGNATURE_IN_BASE64, CertificateLevelCode::QUALIFIED, CertificateLevelCode::QUALIFIED );
  }

  private function createValidationResponseWithLowerCertificateLevelThanRequested(): SmartIdAuthenticationResponse
  {
    return $this->createValidationResponse( SessionEndResultCode::OK, self::VALID_SIGNATURE_IN_BASE64, CertificateLevelCode::ADVANCED, CertificateLevelCode::QUALIFIED );
  }

  /**
   * @return SmartIdAuthenticationResponse
   */
  private function createValidationResponseWithHigherCertificateLevelThanRequested(): SmartIdAuthenticationResponse
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
  private function createValidationResponse(string $endResult, string $signatureInBase64, string $certificateLevel,
                                            string $requestedCertificateLevel ): SmartIdAuthenticationResponse
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
