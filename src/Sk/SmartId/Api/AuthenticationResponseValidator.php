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
namespace Sk\SmartId\Api;

use DirectoryIterator;
use ReflectionClass;
use Sk\SmartId\Api\Data\AuthenticationCertificate;
use Sk\SmartId\Api\Data\AuthenticationIdentity;
use Sk\SmartId\Api\Data\CertificateParser;
use Sk\SmartId\Api\Data\SessionEndResultCode;
use Sk\SmartId\Api\Data\SmartIdAuthenticationResponse;
use Sk\SmartId\Api\Data\SmartIdAuthenticationResult;
use Sk\SmartId\Api\Data\SmartIdAuthenticationResultError;
use Sk\SmartId\Exception\TechnicalErrorException;
use Sk\SmartId\Util\CertificateAttributeUtil;
use Sk\SmartId\Util\NationalIdentityNumberUtil;

class AuthenticationResponseValidator
{
  /**
   * @var array
   */
  private $trustedCACertificates;

  /**
   * @param string|null $resourcesLocation
   */
  public function __construct(string $resourcesLocation = null )
  {
    if ( $resourcesLocation === null )
    {
      $resourcesLocation = __DIR__ . '/../../../resources';
    }

    $this->trustedCACertificates = array();
    $this->initializeTrustedCACertificatesFromResources( $resourcesLocation );
  }

  /**
   * @param SmartIdAuthenticationResponse $authenticationResponse
   * @return SmartIdAuthenticationResult
   */
  public function validate( SmartIdAuthenticationResponse $authenticationResponse ): SmartIdAuthenticationResult
  {
    $this->validateAuthenticationResponse( $authenticationResponse );
    $authenticationResult = new SmartIdAuthenticationResult();
    $identity = $this->constructAuthenticationIdentity( $authenticationResponse->getCertificateInstance(), $authenticationResponse->getCertificate() );
    $authenticationResult->setAuthenticationIdentity( $identity );
    if ( !$this->verifyResponseEndResult( $authenticationResponse ) )
    {
      $authenticationResult->setValid( false );
      $authenticationResult->addError( SmartIdAuthenticationResultError::INVALID_END_RESULT );
    }
    if ( !$this->verifySignature( $authenticationResponse ) )
    {
      $authenticationResult->setValid( false );
      $authenticationResult->addError( SmartIdAuthenticationResultError::SIGNATURE_VERIFICATION_FAILURE );
    }
    if ( !$this->verifyCertificateExpiry( $authenticationResponse->getCertificateInstance() ) )
    {
      $authenticationResult->setValid( false );
      $authenticationResult->addError( SmartIdAuthenticationResultError::CERTIFICATE_EXPIRED );
    }
    if ( !$this->isCertificateTrusted( $authenticationResponse->getCertificate() ) )
    {
      $authenticationResult->setValid( false );
      $authenticationResult->addError( SmartIdAuthenticationResultError::CERTIFICATE_NOT_TRUSTED );
    }
    if ( !$this->verifyCertificateLevel( $authenticationResponse ) )
    {
      $authenticationResult->setValid( false );
      $authenticationResult->addError( SmartIdAuthenticationResultError::CERTIFICATE_LEVEL_MISMATCH );
    }
    return $authenticationResult;
  }

  /**
   * @param SmartIdAuthenticationResponse $authenticationResponse
   * @throws TechnicalErrorException
   */
  private function validateAuthenticationResponse( SmartIdAuthenticationResponse $authenticationResponse )
  {
    if ( $authenticationResponse->getCertificate() === null )
    {
      throw new TechnicalErrorException( 'Certificate is not present in the authentication response' );
    }
    if ( empty( $authenticationResponse->getValueInBase64() ) )
    {
      throw new TechnicalErrorException( 'Signature is not present in the authentication response' );
    }
    if ( empty( $authenticationResponse->getSignedData() ) )
    {
      throw new TechnicalErrorException( 'Signable data is not present in the authentication response' );
    }
  }

  /**
   * @param SmartIdAuthenticationResponse $authenticationResponse
   * @return bool
   */
  private function verifyResponseEndResult( SmartIdAuthenticationResponse $authenticationResponse ): bool
  {
    return strcasecmp( SessionEndResultCode::OK, $authenticationResponse->getEndResult() ) === 0;
  }

  /**
   * @param SmartIdAuthenticationResponse $authenticationResponse
   * @return bool
   */
  private function verifySignature( SmartIdAuthenticationResponse $authenticationResponse ): bool
  {
    $preparedCertificate = CertificateParser::getPemCertificate( $authenticationResponse->getCertificate() );
    $signature = $authenticationResponse->getValue();
    $publicKey = openssl_pkey_get_public( $preparedCertificate );
    if ( $publicKey !== false )
    {
      $data = $authenticationResponse->getSignedData();
      return openssl_verify( $data, $signature, $publicKey, OPENSSL_ALGO_SHA512 ) === 1;
    }
    return false;
  }

  /**
   * @param AuthenticationCertificate $authenticationCertificate
   * @return bool
   */
  private function verifyCertificateExpiry( AuthenticationCertificate $authenticationCertificate ): bool
  {
    return $authenticationCertificate !== null && $authenticationCertificate->getValidTo() > time();
  }

  /**
   * @param SmartIdAuthenticationResponse $authenticationResponse
   * @return bool
   */
  private function verifyCertificateLevel( SmartIdAuthenticationResponse $authenticationResponse ): bool
  {
    $certLevel = new CertificateLevel( $authenticationResponse->getCertificateLevel() );
    $requestedCertificateLevel = $authenticationResponse->getRequestedCertificateLevel();
    return empty($requestedCertificateLevel) || $certLevel->isEqualOrAbove($requestedCertificateLevel);
  }

  /**
   * @param AuthenticationCertificate $certificate
   * @return AuthenticationIdentity
   */
  function constructAuthenticationIdentity( AuthenticationCertificate $certificate, string $x509certificate ): AuthenticationIdentity
  {
    $identity = new AuthenticationIdentity();
    $identity->setAuthCertificate($x509certificate);
    $subject = $certificate->getSubject();
    $subjectReflection = new ReflectionClass( $subject );

    foreach ( $subjectReflection->getProperties() as $property )
    {
      $property->setAccessible( true );
      if ( strcasecmp( $property->getName(), 'GN' ) === 0 )
      {
        $identity->setGivenName( $property->getValue( $subject ) );
      }
      elseif ( strcasecmp( $property->getName(), 'SN' ) === 0 )
      {
        $identity->setSurName( $property->getValue( $subject ) );
      }
      elseif ( strcasecmp( $property->getName(), 'SERIALNUMBER' ) === 0 )
      {
        $serialNumberValue = $property->getValue($subject);
        $identity->setIdentityCode($serialNumberValue);
        $identity->setIdentityNumber( explode('-', $serialNumberValue, 2)[1] );
      }
      elseif ( strcasecmp( $property->getName(), 'C' ) === 0 )
      {
        $identity->setCountry( $property->getValue( $subject ) );
      }
    }

    $identity->setDateOfBirth(self::getDateOfBirth($identity));

    return $identity;
  }

  /**
   * @param string $resourcesLocation
   */
  private function initializeTrustedCACertificatesFromResources(string $resourcesLocation )
  {
    foreach ( new DirectoryIterator( $resourcesLocation . '/trusted_certificates' ) as $certificateFile )
    {
      if ( !$certificateFile->isDir() || !$certificateFile->isDot() )
      {
        $this->addTrustedCACertificateLocation( $certificateFile->getPathname() );
      }
    }
  }

  /**
   * @param string $certificateFileLocation
   * @return $this
   */
  public function addTrustedCACertificateLocation(string $certificateFileLocation ): AuthenticationResponseValidator
  {
    $this->trustedCACertificates[] = $certificateFileLocation;
    return $this;
  }

  /**
   * @return array
   */
  public function getTrustedCACertificates(): array
  {
    return $this->trustedCACertificates;
  }

  /**
   * @return $this
   */
  public function clearTrustedCACertificates(): AuthenticationResponseValidator
  {
    $this->trustedCACertificates = array();
    return $this;
  }

  /**
   * @param string $certificate
   * @return bool
   */
  private function isCertificateTrusted(string $certificate ): bool
  {
    $certificateAsResource = openssl_x509_read( CertificateParser::getPemCertificate( $certificate ) );

    if ( $certificateAsResource === false )
    {
      return false;
    }

    if ( $this->verifyTrustedCACertificates( $certificateAsResource ) === true )
    {
      return true;
    }
    return false;
  }

    /**
     * @param resource $certificateAsResource
     * @return bool
     */
  private function verifyTrustedCACertificates( $certificateAsResource ): bool
  {
    return openssl_x509_checkpurpose( $certificateAsResource, X509_PURPOSE_ANY, $this->trustedCACertificates );
  }

  private static function getDateOfBirth(AuthenticationIdentity $identity) : ?\DateTimeImmutable
  {
    $certificateAttributeUtil = new CertificateAttributeUtil();
    $dateOfBirthFromCertificateField = $certificateAttributeUtil->getDateOfBirthCertificateAttribute($identity->getAuthCertificate());

    if ($dateOfBirthFromCertificateField != null) {
      return $dateOfBirthFromCertificateField;
    }

    $nationalIdentityNumberUtil = new NationalIdentityNumberUtil();
    return $nationalIdentityNumberUtil->getDateOfBirth($identity);
  }
}
