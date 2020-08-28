<?php

namespace Sk\SmartId\Tests;

use Sk\SmartId\Api\Data\AuthenticationSessionRequest;
use Sk\SmartId\Api\Data\DigestCalculator;
use Sk\SmartId\Api\Data\HashType;
use Sk\SmartId\Api\SmartIdRestConnector;
use Sk\SmartId\Tests\Api\DummyData;
use Sk\SmartId\Util\Curl;

class SslTest extends Setup
{

    /**
     * @test
     */
    public function authenticate_demoEnvUseLiveEnvPublicKeys_shouldThrowException()
    {
        try
        {
            Curl::useOnlyLivePublicKey();
            $connector = new SmartIdRestConnector( DummyData::TEST_URL );
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
        catch (\Exception $e) {
            self::assertEquals("SSL: public key does not match pinned public key!", $e->getMessage());
            return;
        }
        self::assertTrue(false);
    }

    /**
     * @test
     */
    public function authenticate_demoEnvSetPublicKeysFromEmptyArray_shouldThrowException()
    {
        try
        {
            Curl::setPublicKeysFromArray(array());
            $connector = new SmartIdRestConnector( DummyData::TEST_URL );
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
        catch (\Exception $e) {
            self::assertEquals("SSL: public key does not match pinned public key!", $e->getMessage());
            return;
        }
        self::assertTrue(false);
    }

    /**
     * @test
     */
    public function makeRequestToGoogle_defaultPublicKeys_shouldThrowException()
    {
        try
        {
            Curl::setPublicKeysFromArray(array());
            $connector = new SmartIdRestConnector( "www.google.com");
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
        catch (\Exception $e) {
            self::assertEquals("User account not found for URI www.google.com/authentication/document/PNOEE-10101010005-Z1B2-Q", $e->getMessage());
            return;
        }
        self::assertTrue(false);
    }
}
