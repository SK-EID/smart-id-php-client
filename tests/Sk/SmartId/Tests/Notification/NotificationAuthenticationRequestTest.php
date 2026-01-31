<?php

/*-
 * #%L
 * Smart ID sample PHP client
 * %%
 * Copyright (C) 2018 - 2026 SK ID Solutions AS
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

declare(strict_types=1);

namespace Sk\SmartId\Tests\Notification;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\Enum\CertificateLevel;
use Sk\SmartId\Enum\HashAlgorithm;
use Sk\SmartId\Model\Interaction;
use Sk\SmartId\Notification\NotificationAuthenticationRequest;

class NotificationAuthenticationRequestTest extends TestCase
{
    #[Test]
    public function constructorSetsRequiredProperties(): void
    {
        $interactions = [Interaction::verificationCodeChoice()];
        $request = new NotificationAuthenticationRequest(
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
        $request = new NotificationAuthenticationRequest(
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
        $request = new NotificationAuthenticationRequest(
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
        $this->assertCount(2, $array['allowedInteractionsOrder']);
        $this->assertArrayNotHasKey('certificateLevel', $array);
        $this->assertArrayNotHasKey('nonce', $array);
        $this->assertArrayNotHasKey('capabilities', $array);
    }

    #[Test]
    public function toArrayIncludesOptionalFieldsWhenSet(): void
    {
        $request = new NotificationAuthenticationRequest(
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
