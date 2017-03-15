<?php
namespace Sk\SmartId\Api;

use Sk\SmartId\Api\Data\AuthenticationCertificate;
use Sk\SmartId\Api\Data\CertificateParser;
use Sk\SmartId\Api\Data\SessionEndResultCode;
use Sk\SmartId\Api\Data\SmartIdAuthenticationResponse;
use Sk\SmartId\Api\Data\SmartIdAuthenticationResult;
use Sk\SmartId\Api\Data\SmartIdAuthenticationResultError;
use Sk\SmartId\Exception\TechnicalErrorException;

class AuthenticationResponseValidator
{
  /**
   * @param SmartIdAuthenticationResponse $authenticationResponse
   * @return SmartIdAuthenticationResult
   */
  public function validate( SmartIdAuthenticationResponse $authenticationResponse )
  {
    $this->validateAuthenticationResponse( $authenticationResponse );
    $authenticationResult = new SmartIdAuthenticationResult();
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
    if ( empty( $authenticationResponse->getRequestedCertificateLevel() ) )
    {
      throw new TechnicalErrorException( 'Requested certificate level is not present in the authentication response' );
    }
  }

  /**
   * @param SmartIdAuthenticationResponse $authenticationResponse
   * @return bool
   */
  private function verifyResponseEndResult( SmartIdAuthenticationResponse $authenticationResponse )
  {
    return strcasecmp( SessionEndResultCode::OK, $authenticationResponse->getEndResult() ) === 0;
  }

  /**
   * @param SmartIdAuthenticationResponse $authenticationResponse
   * @return bool
   */
  private function verifySignature( SmartIdAuthenticationResponse $authenticationResponse )
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
  private function verifyCertificateExpiry( AuthenticationCertificate $authenticationCertificate )
  {
    return $authenticationCertificate !== null && $authenticationCertificate->getValidTo() > time();
  }

  /**
   * @param SmartIdAuthenticationResponse $authenticationResponse
   * @return bool
   */
  private function verifyCertificateLevel( SmartIdAuthenticationResponse $authenticationResponse )
  {
    return strcasecmp( $authenticationResponse->getRequestedCertificateLevel(),
        $authenticationResponse->getCertificateLevel() ) === 0;
  }
}