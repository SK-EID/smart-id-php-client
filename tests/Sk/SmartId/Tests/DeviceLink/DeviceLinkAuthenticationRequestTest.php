<?php

declare(strict_types=1);

namespace Sk\SmartId\Tests\DeviceLink;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\DeviceLink\DeviceLinkAuthenticationRequest;
use Sk\SmartId\Enum\CertificateLevel;
use Sk\SmartId\Enum\HashAlgorithm;
use Sk\SmartId\Model\Interaction;

class DeviceLinkAuthenticationRequestTest extends TestCase
{
    #[Test]
    public function constructorSetsRequiredProperties(): void
    {
        $interactions = [Interaction::verificationCodeChoice()];
        $request = new DeviceLinkAuthenticationRequest(
            relyingPartyUUID: 'rp-uuid',
            relyingPartyName: 'Test RP',
            rpChallenge: 'dGVzdC1jaGFsbGVuZ2U=',
            hashAlgorithm: HashAlgorithm::SHA512,
            allowedInteractionsOrder: $interactions,
        );

        $this->assertSame('rp-uuid', $request->getRelyingPartyUUID());
        $this->assertSame('Test RP', $request->getRelyingPartyName());
        $this->assertSame('dGVzdC1jaGFsbGVuZ2U=', $request->getRpChallenge());
        $this->assertSame(HashAlgorithm::SHA512, $request->getHashAlgorithm());
        $this->assertSame($interactions, $request->getAllowedInteractionsOrder());
        $this->assertNull($request->getCertificateLevel());
    }

    #[Test]
    public function constructorSetsOptionalProperties(): void
    {
        $request = new DeviceLinkAuthenticationRequest(
            relyingPartyUUID: 'rp-uuid',
            relyingPartyName: 'Test RP',
            rpChallenge: 'dGVzdC1jaGFsbGVuZ2U=',
            hashAlgorithm: HashAlgorithm::SHA256,
            allowedInteractionsOrder: [],
            certificateLevel: CertificateLevel::QUALIFIED,
            nonce: 'test-nonce',
            capabilities: ['ADVANCED'],
        );

        $this->assertSame(CertificateLevel::QUALIFIED, $request->getCertificateLevel());
    }

    #[Test]
    public function toArrayReturnsCorrectStructure(): void
    {
        $interactions = [
            Interaction::displayTextAndPin('Please confirm'),
            Interaction::verificationCodeChoice(),
        ];
        $request = new DeviceLinkAuthenticationRequest(
            relyingPartyUUID: 'rp-uuid',
            relyingPartyName: 'Test RP',
            rpChallenge: 'dGVzdC1jaGFsbGVuZ2U=',
            hashAlgorithm: HashAlgorithm::SHA512,
            allowedInteractionsOrder: $interactions,
        );

        $array = $request->toArray();

        $this->assertSame('rp-uuid', $array['relyingPartyUUID']);
        $this->assertSame('Test RP', $array['relyingPartyName']);
        $this->assertSame('ACSP_V2', $array['signatureProtocol']);
        $this->assertArrayHasKey('signatureProtocolParameters', $array);
        $this->assertSame('dGVzdC1jaGFsbGVuZ2U=', $array['signatureProtocolParameters']['rpChallenge']);
        $this->assertSame('rsassa-pss', $array['signatureProtocolParameters']['signatureAlgorithm']);
        $this->assertSame('SHA-512', $array['signatureProtocolParameters']['signatureAlgorithmParameters']['hashAlgorithm']);
        $this->assertArrayHasKey('interactions', $array);
        $decodedInteractions = json_decode(base64_decode($array['interactions']), true);
        $this->assertCount(2, $decodedInteractions);
        $this->assertArrayNotHasKey('certificateLevel', $array);
        $this->assertArrayNotHasKey('nonce', $array);
        $this->assertArrayNotHasKey('capabilities', $array);
    }

    #[Test]
    public function toArrayIncludesOptionalFieldsWhenSet(): void
    {
        $request = new DeviceLinkAuthenticationRequest(
            relyingPartyUUID: 'rp-uuid',
            relyingPartyName: 'Test RP',
            rpChallenge: 'dGVzdC1jaGFsbGVuZ2U=',
            hashAlgorithm: HashAlgorithm::SHA512,
            allowedInteractionsOrder: [],
            certificateLevel: CertificateLevel::QUALIFIED,
            nonce: 'test-nonce',
            capabilities: ['ADVANCED'],
        );

        $array = $request->toArray();

        $this->assertSame('QUALIFIED', $array['certificateLevel']);
        $this->assertSame('test-nonce', $array['nonce']);
        $this->assertSame(['ADVANCED'], $array['capabilities']);
    }
}
