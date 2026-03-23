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
use Sk\SmartId\Enum\HashAlgorithm;
use Sk\SmartId\Session\SignatureAlgorithmParameters;

class SignatureAlgorithmParametersTest extends TestCase
{
    #[Test]
    public function constructorSetsAllProperties(): void
    {
        $params = new SignatureAlgorithmParameters(
            'SHA-256',
            32,
            'BC',
            'id-mgf1',
            'SHA-256',
        );

        $this->assertSame('SHA-256', $params->getHashAlgorithm());
        $this->assertSame(32, $params->getSaltLength());
        $this->assertSame('BC', $params->getTrailerField());
        $this->assertSame('id-mgf1', $params->getMaskGenAlgorithm());
        $this->assertSame('SHA-256', $params->getMaskGenHashAlgorithm());
    }

    #[Test]
    public function constructorWithOnlyHashAlgorithm(): void
    {
        $params = new SignatureAlgorithmParameters('SHA-512');

        $this->assertSame('SHA-512', $params->getHashAlgorithm());
        $this->assertNull($params->getSaltLength());
        $this->assertNull($params->getTrailerField());
        $this->assertNull($params->getMaskGenAlgorithm());
        $this->assertNull($params->getMaskGenHashAlgorithm());
    }

    #[Test]
    public function fromArrayWithAllFields(): void
    {
        $data = [
            'hashAlgorithm' => 'SHA-256',
            'saltLength' => 32,
            'trailerField' => 'BC',
            'maskGenAlgorithm' => [
                'algorithm' => 'id-mgf1',
                'parameters' => [
                    'hashAlgorithm' => 'SHA-256',
                ],
            ],
        ];

        $params = SignatureAlgorithmParameters::fromArray($data);

        $this->assertSame('SHA-256', $params->getHashAlgorithm());
        $this->assertSame(32, $params->getSaltLength());
        $this->assertSame('BC', $params->getTrailerField());
        $this->assertSame('id-mgf1', $params->getMaskGenAlgorithm());
        $this->assertSame('SHA-256', $params->getMaskGenHashAlgorithm());
    }

    #[Test]
    public function fromArrayWithMinimalFields(): void
    {
        $params = SignatureAlgorithmParameters::fromArray([]);

        $this->assertSame('', $params->getHashAlgorithm());
        $this->assertNull($params->getSaltLength());
        $this->assertNull($params->getTrailerField());
        $this->assertNull($params->getMaskGenAlgorithm());
        $this->assertNull($params->getMaskGenHashAlgorithm());
    }

    #[Test]
    public function fromArrayWithNonStringHashAlgorithm(): void
    {
        $params = SignatureAlgorithmParameters::fromArray(['hashAlgorithm' => 123]);

        $this->assertSame('', $params->getHashAlgorithm());
    }

    #[Test]
    public function fromArrayWithNonStringTrailerField(): void
    {
        $params = SignatureAlgorithmParameters::fromArray(['trailerField' => 123]);

        $this->assertNull($params->getTrailerField());
    }

    #[Test]
    public function fromArrayWithMaskGenAlgorithmMissingSubfields(): void
    {
        $params = SignatureAlgorithmParameters::fromArray([
            'maskGenAlgorithm' => [],
        ]);

        $this->assertNull($params->getMaskGenAlgorithm());
        $this->assertNull($params->getMaskGenHashAlgorithm());
    }

    #[Test]
    public function fromArrayWithMaskGenAlgorithmMissingParameters(): void
    {
        $params = SignatureAlgorithmParameters::fromArray([
            'maskGenAlgorithm' => [
                'algorithm' => 'id-mgf1',
            ],
        ]);

        $this->assertSame('id-mgf1', $params->getMaskGenAlgorithm());
        $this->assertNull($params->getMaskGenHashAlgorithm());
    }

    #[Test]
    public function fromArrayWithMaskGenAlgorithmNonArrayParameters(): void
    {
        $params = SignatureAlgorithmParameters::fromArray([
            'maskGenAlgorithm' => [
                'algorithm' => 'id-mgf1',
                'parameters' => 'not-array',
            ],
        ]);

        $this->assertSame('id-mgf1', $params->getMaskGenAlgorithm());
        $this->assertNull($params->getMaskGenHashAlgorithm());
    }

    #[Test]
    public function fromArrayWithMaskGenAlgorithmNonStringHashAlgorithm(): void
    {
        $params = SignatureAlgorithmParameters::fromArray([
            'maskGenAlgorithm' => [
                'algorithm' => 'id-mgf1',
                'parameters' => [
                    'hashAlgorithm' => 123,
                ],
            ],
        ]);

        $this->assertSame('id-mgf1', $params->getMaskGenAlgorithm());
        $this->assertNull($params->getMaskGenHashAlgorithm());
    }

    #[Test]
    public function toFlatArrayWithHashOnly(): void
    {
        $params = new SignatureAlgorithmParameters('SHA-256');

        $this->assertSame(['hashAlgorithm' => 'SHA-256'], $params->toFlatArray());
    }

    #[Test]
    public function toFlatArrayWithSaltLength(): void
    {
        $params = new SignatureAlgorithmParameters('SHA-256', 32);

        $this->assertSame([
            'hashAlgorithm' => 'SHA-256',
            'saltLength' => '32',
        ], $params->toFlatArray());
    }

    #[Test]
    public function getResolvedHashAlgorithmReturnsEnum(): void
    {
        $params = new SignatureAlgorithmParameters('SHA-256');

        $this->assertSame(HashAlgorithm::SHA256, $params->getResolvedHashAlgorithm());
    }

    #[Test]
    public function getResolvedHashAlgorithmReturnsNullForUnknown(): void
    {
        $params = new SignatureAlgorithmParameters('UNKNOWN');

        $this->assertNull($params->getResolvedHashAlgorithm());
    }

    #[Test]
    public function fromArrayWithNonArrayMaskGenAlgorithm(): void
    {
        $params = SignatureAlgorithmParameters::fromArray([
            'maskGenAlgorithm' => 'not-array',
        ]);

        $this->assertNull($params->getMaskGenAlgorithm());
        $this->assertNull($params->getMaskGenHashAlgorithm());
    }
}
