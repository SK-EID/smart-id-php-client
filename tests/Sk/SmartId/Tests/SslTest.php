<?php

namespace Sk\SmartId\Tests;

use Sk\SmartId\Api\Data\AuthenticationHash;
use Sk\SmartId\Api\Data\AuthenticationSessionRequest;
use Sk\SmartId\Api\Data\DigestCalculator;
use Sk\SmartId\Api\Data\HashType;
use Sk\SmartId\Api\SmartIdRestConnector;
use Sk\SmartId\Exception\SmartIdException;
use Sk\SmartId\Tests\Api\DummyData;
use Sk\SmartId\Util\Curl;

class SslTest extends Setup
{
    /**
     * @test
     */
    public function authenticate_demoEnv_success()
    {
        $this->client->authentication()
                ->createAuthentication()
                ->withCertificateLevel(DummyData::CERTIFICATE_LEVEL)
                ->withAuthenticationHash(new AuthenticationHash(DigestCalculator::calculateDigest( DummyData::SIGNABLE_TEXT, HashType::SHA512 )))
                ->withDocumentNumber(DummyData::VALID_DOCUMENT_NUMBER)
                ->authenticate();
    }

    /**
     * @test
     */
    public function authenticate_demoEnvUseDemoEnvPublicKeys_success()
    {
        $this->client->useOnlyDemoPublicKey()->authentication()
                ->createAuthentication()
                ->withCertificateLevel(DummyData::CERTIFICATE_LEVEL)
                ->withAuthenticationHash(new AuthenticationHash(DigestCalculator::calculateDigest( DummyData::SIGNABLE_TEXT, HashType::SHA512 )))
                ->withDocumentNumber(DummyData::VALID_DOCUMENT_NUMBER)
                ->authenticate();
    }


    /**
     * @test
     */
    public function authenticate_demoEnvUseLiveEnvPublicKeys_shouldThrowException()
    {
        $this->expectException(SmartIdException::class);

        $this->client->useOnlyLivePublicKey()->authentication()
                ->createAuthentication()
                ->withCertificateLevel(DummyData::CERTIFICATE_LEVEL)
                ->withAuthenticationHash(new AuthenticationHash(DigestCalculator::calculateDigest( DummyData::SIGNABLE_TEXT, HashType::SHA512 )))
                ->withDocumentNumber(DummyData::VALID_DOCUMENT_NUMBER)
                ->authenticate();
    }

    /**
 * @test
 */
    public function authenticate_demoEnvSetPublicKeysFromArray_success()
    {
        $this->client->setPublicSslKeys("sha256//+Tz0G7u3vgcaw/o32vIoCNNjpfo8UugQEXmWkrCuc4o=;sha256//wkdgNtKpKzMtH/zoLkgeScp1Ux4TLm3sUldobVGA/g4=")->authentication()
                ->createAuthentication()
                ->withCertificateLevel(DummyData::CERTIFICATE_LEVEL)
                ->withAuthenticationHash(new AuthenticationHash(DigestCalculator::calculateDigest( DummyData::SIGNABLE_TEXT, HashType::SHA512 )))
                ->withDocumentNumber(DummyData::VALID_DOCUMENT_NUMBER)
                ->authenticate();
    }

    /**
     * @test
     */
    public function authenticate_demoEnvSetPublicKeysFromEmptyString_throwsException()
    {
        $this->expectException(SmartIdException::class);
        $this->client->setPublicSslKeys("")->authentication()
                ->createAuthentication()
                ->withCertificateLevel(DummyData::CERTIFICATE_LEVEL)
                ->withAuthenticationHash(new AuthenticationHash(DigestCalculator::calculateDigest( DummyData::SIGNABLE_TEXT, HashType::SHA512 )))
                ->withDocumentNumber(DummyData::VALID_DOCUMENT_NUMBER)
                ->authenticate();
    }

    /**
     * @test
     */
    public function makeRequestToGoogle_demoPublicKeys_shouldThrowException()
    {
        $this->expectException(SmartIdException::class);
        $this->client
                ->setHostUrl("https://www.google.com")
                ->useOnlyDemoPublicKey()->authentication()
                ->createAuthentication()
                ->withCertificateLevel(DummyData::CERTIFICATE_LEVEL)
                ->withAuthenticationHash(new AuthenticationHash(DigestCalculator::calculateDigest( DummyData::SIGNABLE_TEXT, HashType::SHA512 )))
                ->withDocumentNumber(DummyData::VALID_DOCUMENT_NUMBER)
                ->authenticate();
    }
}
