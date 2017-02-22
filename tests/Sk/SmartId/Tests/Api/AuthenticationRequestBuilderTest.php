<?php
namespace Sk\SmartId\Tests\Api;

use Sk\SmartId\Api\AuthenticationRequestBuilder;
use Sk\SmartId\Api\Data\AuthenticationSessionResponse;
use Sk\SmartId\Api\Data\HashType;
use Sk\SmartId\Api\Data\NationalIdentity;
use Sk\SmartId\Api\Data\SignableData;
use Sk\SmartId\Api\Data\SignableHash;
use Sk\SmartId\Tests\Rest\SmartIdConnectorSpy;
use Sk\SmartId\Tests\Setup;

class AuthenticationRequestBuilderTest extends Setup
{
  /**
   * @var SmartIdConnectorSpy
   */
  private $connector;

  /**
   * @var AuthenticationRequestBuilder
   */
  private $builder;

  protected function setUp()
  {
    $this->connector = new SmartIdConnectorSpy();
    $this->connector->authenticationSessionResponseToRespond = $this->createDummyAuthenticationSessionResponse();
    $this->builder = new AuthenticationRequestBuilder( $this->connector );
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
    $this->assertCorrectSignatureRequestMadeWithDocumentNumber();
    $this->assertAuthenticationResultCorrect( $authenticationResult );
  }

  /**
   * @test
   */
  public function authenticateWithSignableHash()
  {
    $hashToSign = new SignableHash();
    $hashToSign->setHashType( HashType::SHA512 );
    $hashToSign->setHashInBase64( '7iaw3Ur350mqGo7jwQrpkj9hiYB3Lkc/iBml1JQODbJ6wYX4oOHV+E+IvIh/1nsUNzLDBMxfqa2Ob1f1ACio/w==' );
    $authenticationResult = $this->builder
        ->withRelyingPartyUUID( 'relying-party-uuid' )
        ->withRelyingPartyName( 'relying-party-name' )
        ->withCertificateLevel( 'ADVANCED' )
        ->withHash( $hashToSign )
        ->withDocumentNumber( 'PNOEE-31111111111' )
        ->authenticate();
    $this->assertCorrectSignatureRequestMadeWithDocumentNumber();
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
    $this->assertCorrectSignatureRequestMadeWithDocumentNumber();
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
  public function authenticateWithSignableHash_withoutHashType_shouldThrowException()
  {
    $hashToSign = new SignableHash();
    $hashToSign->setHashInBase64( '7iaw3Ur350mqGo7jwQrpkj9hiYB3Lkc/iBml1JQODbJ6wYX4oOHV+E+IvIh/1nsUNzLDBMxfqa2Ob1f1ACio/w==' );
    $this->builder
        ->withRelyingPartyUUID( 'relying-party-uuid' )
        ->withRelyingPartyName( 'relying-party-name' )
        ->withCertificateLevel( 'ADVANCED' )
        ->withHash( $hashToSign )
        ->withDocumentNumber( 'PNOEE-31111111111' )
        ->authenticate();
  }

  /**
   * @test
   * @expectedException \Sk\SmartId\Exception\InvalidParametersException
   */
  public function authenticateWithSignableHash_withoutHash_shouldThrowException()
  {
    $hashToSign = new SignableHash();
    $hashToSign->setHashType( HashType::SHA512 );
    $this->builder
        ->withRelyingPartyUUID( 'relying-party-uuid' )
        ->withRelyingPartyName( 'relying-party-name' )
        ->withCertificateLevel( 'ADVANCED' )
        ->withHash( $hashToSign )
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
   * @return AuthenticationSessionResponse
   */
  private function createDummyAuthenticationSessionResponse()
  {
    $response = new AuthenticationSessionResponse();
    $response->setSessionId( '97f5058e-e308-4c83-ac14-7712b0eb9d86' );
    return $response;
  }

  private function assertCorrectSignatureRequestMadeWithDocumentNumber()
  {
    $this->assertEquals( 'PNOEE-31111111111', $this->connector->documentNumberUsed );
    $this->assertEquals( 'relying-party-uuid', $this->connector->authenticationSessionRequestUsed->getRelyingPartyUUID() );
    $this->assertEquals( 'relying-party-name', $this->connector->authenticationSessionRequestUsed->getRelyingPartyName() );
    $this->assertEquals( 'ADVANCED', $this->connector->authenticationSessionRequestUsed->getCertificateLevel() );
    $this->assertEquals( HashType::SHA512, $this->connector->authenticationSessionRequestUsed->getHashType() );
    $this->assertEquals( '7iaw3Ur350mqGo7jwQrpkj9hiYB3Lkc/iBml1JQODbJ6wYX4oOHV+E+IvIh/1nsUNzLDBMxfqa2Ob1f1ACio/w==',
        $this->connector->authenticationSessionRequestUsed->getHash() );
  }

  /**
   * @param AuthenticationSessionResponse $authenticationResult
   */
  private function assertAuthenticationResultCorrect( AuthenticationSessionResponse $authenticationResult )
  {
    $this->assertNotNull( $authenticationResult );
    $this->assertEquals( '97f5058e-e308-4c83-ac14-7712b0eb9d86', $authenticationResult->getSessionID() );
  }

  private function assertCorrectSignatureRequestMadeWithNationalIdentity()
  {
    $this->assertEquals( '31111111111', $this->connector->identityUsed->getNationalIdentityNumber() );
    $this->assertEquals( 'EE', $this->connector->identityUsed->getCountryCode() );
    $this->assertEquals( 'relying-party-uuid', $this->connector->authenticationSessionRequestUsed->getRelyingPartyUUID() );
    $this->assertEquals( 'relying-party-name', $this->connector->authenticationSessionRequestUsed->getRelyingPartyName() );
    $this->assertEquals( 'ADVANCED', $this->connector->authenticationSessionRequestUsed->getCertificateLevel() );
    $this->assertEquals( HashType::SHA512, $this->connector->authenticationSessionRequestUsed->getHashType() );
    $this->assertEquals( '7iaw3Ur350mqGo7jwQrpkj9hiYB3Lkc/iBml1JQODbJ6wYX4oOHV+E+IvIh/1nsUNzLDBMxfqa2Ob1f1ACio/w==',
        $this->connector->authenticationSessionRequestUsed->getHash() );
  }
}