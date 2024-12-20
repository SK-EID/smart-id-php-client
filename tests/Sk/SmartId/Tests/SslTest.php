<?php

namespace Sk\SmartId\Tests;

use Sk\SmartId\Api\Data\AuthenticationSessionRequest;
use Sk\SmartId\Api\Data\DigestCalculator;
use Sk\SmartId\Api\Data\HashType;
use Sk\SmartId\Api\SmartIdRestConnector;
use Sk\SmartId\Tests\Api\DummyData;

class SslTest extends Setup
{

    /**
     * @test
     */
    public function authenticate_demoEnvUseLiveEnvPublicKeys_shouldThrowException()
    {
        $this->expectExceptionMessage("SSL: public key does not match pinned public key");
        $connector = new SmartIdRestConnector( DummyData::TEST_URL );
        $connector->setPublicSslKeys("sha256//l2uvq6ftLN4LZ+8Un+71J2vH1BT9wTbtrE5+Fj3Vc5g=;");
        $authenticationSessionRequest = new AuthenticationSessionRequest();
        $authenticationSessionRequest
                ->setRelyingPartyUUID( DummyData::DEMO_RELYING_PARTY_UUID )
                ->setRelyingPartyName( DummyData::DEMO_RELYING_PARTY_NAME )
                ->setCertificateLevel( DummyData::CERTIFICATE_LEVEL )
                ->setHashType( HashType::SHA512 );
        $hashInBase64 = base64_encode(DigestCalculator::calculateDigest( DummyData::SIGNABLE_TEXT, HashType::SHA512 ));
        $authenticationSessionRequest->setHash( $hashInBase64 );


        $authenticateSessionResponse =
                $connector->authenticate(DummyData::VALID_DOCUMENT_NUMBER,
                        $authenticationSessionRequest);
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function authenticate_demoEnvSetPublicKeysFromEmptyArray_httpsPinningNotUsed_exceptionNotThrown()
    {
        $connector = new SmartIdRestConnector( DummyData::TEST_URL );
        $connector->setPublicSslKeys("");
        $authenticationSessionRequest = new AuthenticationSessionRequest();
        $authenticationSessionRequest
                ->setRelyingPartyUUID( DummyData::DEMO_RELYING_PARTY_UUID )
                ->setRelyingPartyName( DummyData::DEMO_RELYING_PARTY_NAME )
                ->setCertificateLevel( DummyData::CERTIFICATE_LEVEL )
                ->setHashType( HashType::SHA512 );
        $hashInBase64 = base64_encode(DigestCalculator::calculateDigest( DummyData::SIGNABLE_TEXT, HashType::SHA512 ));
        $authenticationSessionRequest->setHash( $hashInBase64 );

        $authenticateSessionResponse =
                $connector->authenticate(DummyData::VALID_DOCUMENT_NUMBER,
                        $authenticationSessionRequest);
    }

    /**
     * @test
     */
    public function makeRequestToGoogle_defaultPublicKeys_shouldThrowException()
    {
        $this->expectExceptionMessage("User account not found for URI www.google.com/authentication/document/" . DummyData::VALID_DOCUMENT_NUMBER);

        $connector = new SmartIdRestConnector( "www.google.com");
        $connector->setPublicSslKeys("");
        $authenticationSessionRequest = new AuthenticationSessionRequest();
        $authenticationSessionRequest
                ->setRelyingPartyUUID( DummyData::DEMO_RELYING_PARTY_UUID )
                ->setRelyingPartyName( DummyData::DEMO_RELYING_PARTY_NAME )
                ->setCertificateLevel( DummyData::CERTIFICATE_LEVEL )
                ->setHashType( HashType::SHA512 );
        $hashInBase64 = base64_encode(DigestCalculator::calculateDigest( DummyData::SIGNABLE_TEXT, HashType::SHA512 ));
        $authenticationSessionRequest->setHash( $hashInBase64 );

        $authenticateSessionResponse =
                $connector->authenticate(DummyData::VALID_DOCUMENT_NUMBER,
                        $authenticationSessionRequest);
    }
}
