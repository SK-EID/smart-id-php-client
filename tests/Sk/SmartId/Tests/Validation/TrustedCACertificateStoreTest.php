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

namespace Sk\SmartId\Tests\Validation;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\Validation\AuthenticationResponseValidator;
use Sk\SmartId\Validation\OcspCertificateRevocationChecker;
use Sk\SmartId\Validation\TrustedCACertificateStore;

class TrustedCACertificateStoreTest extends TestCase
{
    #[Test]
    public function loadFromDefaultsReturnsCertificates(): void
    {
        $store = TrustedCACertificateStore::loadFromDefaults();

        $certificates = $store->getCertificates();

        $this->assertNotEmpty($certificates);
        $this->assertContainsOnly('string', $certificates);
    }

    #[Test]
    public function createReturnsEmptyStore(): void
    {
        $store = TrustedCACertificateStore::create();

        $this->assertEmpty($store->getCertificates());
    }

    #[Test]
    public function addCertificateAddsCertificate(): void
    {
        $store = TrustedCACertificateStore::create();
        $store->addCertificate('-----BEGIN CERTIFICATE-----TEST-----END CERTIFICATE-----');

        $this->assertCount(1, $store->getCertificates());
    }

    #[Test]
    public function configureValidatorSetsCertificates(): void
    {
        $store = TrustedCACertificateStore::loadFromDefaults();
        $validator = new AuthenticationResponseValidator();

        $result = $store->configureValidator($validator);

        $this->assertSame($validator, $result);
        $this->assertNotEmpty($validator->getTrustedCaCertificates());
        $this->assertSame($store->getCertificates(), $validator->getTrustedCaCertificates());
    }

    #[Test]
    public function loadFromDirectoryThrowsForEmptyDirectory(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No certificate files found');

        TrustedCACertificateStore::loadFromDirectory(sys_get_temp_dir());
    }

    #[Test]
    public function configureValidatorWithOcspSetsCertificatesAndOcsp(): void
    {
        $store = TrustedCACertificateStore::loadFromDefaults();
        $validator = new AuthenticationResponseValidator();

        $result = $store->configureValidatorWithOcsp($validator);

        $this->assertSame($validator, $result);
        $this->assertNotEmpty($validator->getTrustedCaCertificates());
        $this->assertSame($store->getCertificates(), $validator->getTrustedCaCertificates());
    }

    #[Test]
    public function configureValidatorWithOcspAcceptsCustomChecker(): void
    {
        $store = TrustedCACertificateStore::loadFromDefaults();
        $validator = new AuthenticationResponseValidator();
        $checker = new OcspCertificateRevocationChecker();

        $result = $store->configureValidatorWithOcsp($validator, $checker);

        $this->assertSame($validator, $result);
        $this->assertNotEmpty($validator->getTrustedCaCertificates());
    }

    #[Test]
    public function loadTestCertificatesReturnsCertificates(): void
    {
        $store = TrustedCACertificateStore::loadTestCertificates();

        $certificates = $store->getCertificates();

        $this->assertNotEmpty($certificates);
        $this->assertContainsOnly('string', $certificates);
    }
}
