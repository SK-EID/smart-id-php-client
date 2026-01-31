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

namespace Sk\SmartId\Tests\Session;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\Exception\ValidationException;
use Sk\SmartId\Session\SessionSignature;

class SessionSignatureTest extends TestCase
{
    #[Test]
    public function constructorSetsProperties(): void
    {
        $sig = new SessionSignature('signatureValue', 'sha512WithRSAEncryption');

        $this->assertSame('signatureValue', $sig->getValue());
        $this->assertSame('sha512WithRSAEncryption', $sig->getSignatureAlgorithm());
    }

    #[Test]
    public function fromArrayCreatesSignature(): void
    {
        $data = [
            'value' => 'base64SignatureValue',
            'signatureAlgorithm' => 'sha256WithRSAEncryption',
            'serverRandom' => 'randomValue',
            'userChallenge' => 'challengeValue',
            'flowType' => 'QR',
        ];

        $sig = SessionSignature::fromArray($data);

        $this->assertSame('base64SignatureValue', $sig->getValue());
        $this->assertSame('sha256WithRSAEncryption', $sig->getSignatureAlgorithm());
        $this->assertSame('randomValue', $sig->getServerRandom());
        $this->assertSame('challengeValue', $sig->getUserChallenge());
        $this->assertSame('QR', $sig->getFlowType());
    }

    #[Test]
    public function getDecodedValueReturnsDecodedBase64(): void
    {
        $originalValue = 'test signature data';
        $base64Value = base64_encode($originalValue);
        $sig = new SessionSignature($base64Value, 'sha512WithRSAEncryption');

        $decoded = $sig->getDecodedValue();

        $this->assertSame($originalValue, $decoded);
    }

    #[Test]
    public function getDecodedValueThrowsOnInvalidBase64(): void
    {
        $sig = new SessionSignature('not-valid-base64!!!', 'sha512WithRSAEncryption');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid base64 encoded signature value');

        $sig->getDecodedValue();
    }
}
