<?php
namespace Sk\SmartId\Tests\Api;

use Sk\SmartId\Api\AuthenticationRequestBuilder;
use Sk\SmartId\Api\Data\AuthenticationSessionResponse;
use Sk\SmartId\Api\Data\HashType;
use Sk\SmartId\Api\Data\NationalIdentity;
use Sk\SmartId\Api\Data\SessionCertificate;
use Sk\SmartId\Api\Data\SessionEndResultCode;
use Sk\SmartId\Api\Data\SessionSignature;
use Sk\SmartId\Api\Data\SessionStatus;
use Sk\SmartId\Api\Data\SessionStatusCode;
use Sk\SmartId\Api\Data\SignableData;
use Sk\SmartId\Api\Data\SignableDataGenerator;
use Sk\SmartId\Api\Data\SmartIdAuthenticationResult;
use Sk\SmartId\Api\SessionStatusPoller;
use Sk\SmartId\Tests\Rest\SmartIdConnectorSpy;
use Sk\SmartId\Tests\Setup;

class AuthenticationRequestBuilderTest extends Setup
{
  /**
   * @var SmartIdConnectorSpy
   */
  private $connector;

  /**
   * @var SessionStatusPoller
   */
  private $sessionStatusPoller;

  /**
   * @var AuthenticationRequestBuilder
   */
  private $builder;

  protected function setUp()
  {
    $this->connector = new SmartIdConnectorSpy();
    $this->sessionStatusPoller = new SessionStatusPoller( $this->connector );
    $this->connector->authenticationSessionResponseToRespond = $this->createDummyAuthenticationSessionResponse();
    $this->connector->sessionStatusToRespond = $this->createDummySessionStatusResponse();
    $this->builder = new AuthenticationRequestBuilder( $this->connector, $this->sessionStatusPoller );
  }

  /**
   * @test
   */
  public function authenticateWithDocumentNumberAndGeneratedSignableData()
  {
    $dataToSign = SignableDataGenerator::generate( HashType::SHA512 );
    $authenticationResult = $this->builder
        ->withRelyingPartyUUID( 'relying-party-uuid' )
        ->withRelyingPartyName( 'relying-party-name' )
        ->withCertificateLevel( 'ADVANCED' )
        ->withDocumentNumber( 'PNOEE-31111111111' )
        ->withSignableData( $dataToSign )
        ->authenticate();
    $this->assertCorrectAuthenticationRequestMadeWithDocumentNumber();
    $this->assertGeneratedHash( $dataToSign );
    $this->assertCorrectSessionRequestMade();
    $this->assertAuthenticationResultCorrect( $authenticationResult );
  }

  /**
   * @test
   */
  public function authenticateWithDocumentNumberAndHashInBase64()
  {
    $authenticationResult = $this->builder
        ->withRelyingPartyUUID( 'relying-party-uuid' )
        ->withRelyingPartyName( 'relying-party-name' )
        ->withCertificateLevel( 'ADVANCED' )
        ->withHashInBase64( '7iaw3Ur350mqGo7jwQrpkj9hiYB3Lkc/iBml1JQODbJ6wYX4oOHV+E+IvIh/1nsUNzLDBMxfqa2Ob1f1ACio/w==' )
        ->withHashType( HashType::SHA512 )
        ->withDocumentNumber( 'PNOEE-31111111111' )
        ->authenticate();
    $this->assertCorrectAuthenticationRequestMadeWithDocumentNumber();
    $this->assertFixedHash();
    $this->assertCorrectSessionRequestMade();
    $this->assertAuthenticationResultCorrect( $authenticationResult );
  }

  /**
   * @test
   */
  public function authenticateWithSignableData()
  {
    $dataToSign = new SignableData( 'test' );
    $dataToSign->setHashType( HashType::SHA512 );
    $authenticationResult = $this->builder
        ->withRelyingPartyUUID( 'relying-party-uuid' )
        ->withRelyingPartyName( 'relying-party-name' )
        ->withCertificateLevel( 'ADVANCED' )
        ->withSignableData( $dataToSign )
        ->withDocumentNumber( 'PNOEE-31111111111' )
        ->authenticate();
    $this->assertCorrectAuthenticationRequestMadeWithDocumentNumber();
    $this->assertFixedHash();
    $this->assertCorrectSessionRequestMade();
    $this->assertAuthenticationResultCorrect( $authenticationResult );
  }

  /**
   * @test
   */
  public function authenticateUsingNationalIdentityNumberAndCountryCode()
  {
    $dataToSign = new SignableData( 'test' );
    $dataToSign->setHashType( HashType::SHA512 );
    $authenticationResult = $this->builder
        ->withRelyingPartyUUID( 'relying-party-uuid' )
        ->withRelyingPartyName( 'relying-party-name' )
        ->withCertificateLevel( 'ADVANCED' )
        ->withSignableData( $dataToSign )
        ->withNationalIdentityNumber( '31111111111' )
        ->withCountryCode( 'EE' )
        ->authenticate();
    $this->assertCorrectSignatureRequestMadeWithNationalIdentity();
    $this->assertFixedHash();
    $this->assertCorrectSessionRequestMade();
    $this->assertAuthenticationResultCorrect( $authenticationResult );
  }

  /**
   * @test
   */
  public function authenticateUsingNationalIdentity()
  {
    $identity = new NationalIdentity( 'EE', '31111111111' );
    $authenticationResult = $this->builder
        ->withRelyingPartyUUID( 'relying-party-uuid' )
        ->withRelyingPartyName( 'relying-party-name' )
        ->withCertificateLevel( 'ADVANCED' )
        ->withHashInBase64( '7iaw3Ur350mqGo7jwQrpkj9hiYB3Lkc/iBml1JQODbJ6wYX4oOHV+E+IvIh/1nsUNzLDBMxfqa2Ob1f1ACio/w==' )
        ->withHashType( HashType::SHA512 )
        ->withNationalIdentity( $identity )
        ->authenticate();
    $this->assertCorrectSignatureRequestMadeWithNationalIdentity();
    $this->assertFixedHash();
    $this->assertCorrectSessionRequestMade();
    $this->assertAuthenticationResultCorrect( $authenticationResult );
  }

  /**
   * @test
   * @expectedException \Sk\SmartId\Exception\InvalidParametersException
   */
  public function authenticateWithoutDocumentNumberNorNationalIdentity_shouldThrowException()
  {
    $this->builder
        ->withRelyingPartyUUID( 'relying-party-uuid' )
        ->withRelyingPartyName( 'relying-party-name' )
        ->withCertificateLevel( 'ADVANCED' )
        ->withHashInBase64( '7iaw3Ur350mqGo7jwQrpkj9hiYB3Lkc/iBml1JQODbJ6wYX4oOHV+E+IvIh/1nsUNzLDBMxfqa2Ob1f1ACio/w==' )
        ->withHashType( HashType::SHA512 )
        ->authenticate();
  }

  /**
   * @test
   * @expectedException \Sk\SmartId\Exception\InvalidParametersException
   */
  public function authenticateWithoutCertificateLevel_shouldThrowException()
  {
    $this->builder
        ->withRelyingPartyUUID( 'relying-party-uuid' )
        ->withRelyingPartyName( 'relying-party-name' )
        ->withHashInBase64( '7iaw3Ur350mqGo7jwQrpkj9hiYB3Lkc/iBml1JQODbJ6wYX4oOHV+E+IvIh/1nsUNzLDBMxfqa2Ob1f1ACio/w==' )
        ->withHashType( HashType::SHA512 )
        ->withDocumentNumber( 'PNOEE-31111111111' )
        ->authenticate();
  }

  /**
   * @test
   * @expectedException \Sk\SmartId\Exception\InvalidParametersException
   */
  public function authenticateWithoutHash_andWithoutData_shouldThrowException()
  {
    $this->builder
        ->withRelyingPartyUUID( 'relying-party-uuid' )
        ->withRelyingPartyName( 'relying-party-name' )
        ->withCertificateLevel( 'ADVANCED' )
        ->withDocumentNumber( 'PNOEE-31111111111' )
        ->authenticate();
  }

  /**
   * @test
   * @expectedException \Sk\SmartId\Exception\InvalidParametersException
   */
  public function authenticateWithoutHashType_shouldThrowException()
  {
    $this->builder
        ->withRelyingPartyUUID( 'relying-party-uuid' )
        ->withRelyingPartyName( 'relying-party-name' )
        ->withCertificateLevel( 'ADVANCED' )
        ->withHashInBase64( '7iaw3Ur350mqGo7jwQrpkj9hiYB3Lkc/iBml1JQODbJ6wYX4oOHV+E+IvIh/1nsUNzLDBMxfqa2Ob1f1ACio/w==' )
        ->withDocumentNumber( 'PNOEE-31111111111' )
        ->authenticate();
  }

  /**
   * @test
   * @expectedException \Sk\SmartId\Exception\InvalidParametersException
   */
  public function authenticateWithoutRelyingPartyUuid_shouldThrowException()
  {
    $this->builder
        ->withRelyingPartyName( 'relying-party-name' )
        ->withCertificateLevel( 'ADVANCED' )
        ->withHashType( HashType::SHA512 )
        ->withHashInBase64( '7iaw3Ur350mqGo7jwQrpkj9hiYB3Lkc/iBml1JQODbJ6wYX4oOHV+E+IvIh/1nsUNzLDBMxfqa2Ob1f1ACio/w==' )
        ->withDocumentNumber( 'PNOEE-31111111111' )
        ->authenticate();
  }

  /**
   * @test
   * @expectedException \Sk\SmartId\Exception\InvalidParametersException
   */
  public function authenticateWithoutRelyingPartyName_shouldThrowException()
  {
    $this->builder
        ->withRelyingPartyUUID( 'relying-party-uuid' )
        ->withCertificateLevel( 'ADVANCED' )
        ->withHashType( HashType::SHA512 )
        ->withHashInBase64( '0nbgC2fVdLVQFZJdBbmG7oPoElpCYsQMtrY0c0wKYRg=' )
        ->withDocumentNumber( 'PNOEE-31111111111' )
        ->authenticate();
  }

  /**
   * @test
   * @expectedException \Sk\SmartId\Exception\UserRefusedException
   */
  public function authenticate_withUserRefused_shouldThrowException()
  {
    $this->connector->sessionStatusToRespond = DummyData::createUserRefusedSessionStatus();
    $this->makeAuthenticationRequest();
  }

  /**
   * @test
   * @expectedException \Sk\SmartId\Exception\TechnicalErrorException
   */
  public function authenticate_withResultMissingInResponse_shouldThrowException()
  {
    $this->connector->sessionStatusToRespond->setResult( null );
    $this->makeAuthenticationRequest();
  }

  /**
   * @test
   * @expectedException \Sk\SmartId\Exception\TechnicalErrorException
   */
  public function authenticate_withSignatureMissingInResponse_shouldThrowException()
  {
    $this->connector->sessionStatusToRespond->setSignature( null );
    $this->makeAuthenticationRequest();
  }

  /**
   * @test
   * @expectedException \Sk\SmartId\Exception\TechnicalErrorException
   */
  public function authenticate_withCertificateMissingInResponse_shouldThrowException()
  {
    $this->connector->sessionStatusToRespond->setCert( null );
    $this->makeAuthenticationRequest();
  }

  private function assertCorrectAuthenticationRequestMadeWithDocumentNumber()
  {
    $this->assertEquals( 'PNOEE-31111111111', $this->connector->documentNumberUsed );
    $this->assertEquals( 'relying-party-uuid', $this->connector->authenticationSessionRequestUsed->getRelyingPartyUUID() );
    $this->assertEquals( 'relying-party-name', $this->connector->authenticationSessionRequestUsed->getRelyingPartyName() );
    $this->assertEquals( 'ADVANCED', $this->connector->authenticationSessionRequestUsed->getCertificateLevel() );
  }

  private function assertCorrectSignatureRequestMadeWithNationalIdentity()
  {
    $this->assertEquals( '31111111111', $this->connector->identityUsed->getNationalIdentityNumber() );
    $this->assertEquals( 'EE', $this->connector->identityUsed->getCountryCode() );
    $this->assertEquals( 'relying-party-uuid', $this->connector->authenticationSessionRequestUsed->getRelyingPartyUUID() );
    $this->assertEquals( 'relying-party-name', $this->connector->authenticationSessionRequestUsed->getRelyingPartyName() );
    $this->assertEquals( 'ADVANCED', $this->connector->authenticationSessionRequestUsed->getCertificateLevel() );
  }

  private function assertCorrectSessionRequestMade()
  {
    $this->assertEquals( '97f5058e-e308-4c83-ac14-7712b0eb9d86', $this->connector->sessionIdUsed );
  }

  /**
   * @param SmartIdAuthenticationResult $authenticationResult
   */
  private function assertAuthenticationResultCorrect( SmartIdAuthenticationResult $authenticationResult )
  {
    $this->assertNotNull( $authenticationResult );
    $this->assertEquals( SessionEndResultCode::OK, $authenticationResult->getEndResult() );
    $this->assertEquals( 'c2FtcGxlIHNpZ25hdHVyZQ0K', $authenticationResult->getValueInBase64() );
    $this->assertEquals( 'sha512WithRSAEncryption', $authenticationResult->getAlgorithmName() );
    $this->assertEquals( 'PNOEE-31111111111', $authenticationResult->getDocumentNumber() );
    $this->assertEquals( DummyData::CERTIFICATE, $authenticationResult->getCertificate() );
    $this->assertEquals( 'QUALIFIED', $authenticationResult->getCertificateLevel() );
  }

  /**
   * @return SessionStatus
   */
  private function createDummySessionStatusResponse()
  {
    $signature = new SessionSignature();
    $signature->setValue( 'c2FtcGxlIHNpZ25hdHVyZQ0K' );
    $signature->setAlgorithm( 'sha512WithRSAEncryption' );

    $certificate = new SessionCertificate();
    $certificate->setCertificateLevel( 'QUALIFIED' );
    $certificate->setValue( DummyData::CERTIFICATE );

    $status = new SessionStatus();
    $status
        ->setState( SessionStatusCode::COMPLETE )
        ->setResult( DummyData::createSessionEndResult() )
        ->setSignature( $signature )
        ->setCert( $certificate );
    return $status;
  }

  /**
   * @return AuthenticationSessionResponse
   */
  private function createDummyAuthenticationSessionResponse()
  {
    $response = new AuthenticationSessionResponse();
    $response->setSessionId( '97f5058e-e308-4c83-ac14-7712b0eb9d86' );
    return $response;
  }

  private function makeAuthenticationRequest()
  {
    $this->builder
        ->withRelyingPartyUUID( 'relying-party-uuid' )
        ->withRelyingPartyName( 'relying-party-name' )
        ->withCertificateLevel( 'ADVANCED' )
        ->withHashType( HashType::SHA256 )
        ->withHashInBase64( 'jsflWgpkVcWOyICotnVn5lazcXdaIWvcvNOWTYPceYQ=' )
        ->withDocumentNumber( 'PNOEE-31111111111' )
        ->authenticate();
  }

  private function assertFixedHash()
  {
    $this->assertEquals( HashType::SHA512, $this->connector->authenticationSessionRequestUsed->getHashType() );
    $this->assertEquals( '7iaw3Ur350mqGo7jwQrpkj9hiYB3Lkc/iBml1JQODbJ6wYX4oOHV+E+IvIh/1nsUNzLDBMxfqa2Ob1f1ACio/w==',
        $this->connector->authenticationSessionRequestUsed->getHash() );
  }

  /**
   * @param SignableData $dataToSign
   */
  private function assertGeneratedHash( SignableData $dataToSign )
  {
    $this->assertEquals( $dataToSign->getHashType(), $this->connector->authenticationSessionRequestUsed->getHashType() );
    $this->assertEquals( $dataToSign->calculateHashInBase64(), $this->connector->authenticationSessionRequestUsed->getHash() );
  }
}