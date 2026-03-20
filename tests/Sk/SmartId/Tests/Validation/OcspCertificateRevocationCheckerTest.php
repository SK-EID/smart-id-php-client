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

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\Exception\ValidationException;
use Sk\SmartId\Validation\OcspCertificateRevocationChecker;

class OcspCertificateRevocationCheckerTest extends TestCase
{
    private static function getTestCertificatesDir(): string
    {
        return dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'trusted_certificates';
    }

    #[Test]
    public function constructorCreatesDefaultHttpClient(): void
    {
        $checker = new OcspCertificateRevocationChecker();
        $this->assertInstanceOf(OcspCertificateRevocationChecker::class, $checker);
    }

    #[Test]
    public function constructorAcceptsCustomHttpClient(): void
    {
        $client = new Client();
        $checker = new OcspCertificateRevocationChecker($client, 5);
        $this->assertInstanceOf(OcspCertificateRevocationChecker::class, $checker);
    }

    #[Test]
    public function checkRevocationStatusThrowsForCertWithoutOcspUrl(): void
    {
        $checker = new OcspCertificateRevocationChecker();

        $certWithoutOcsp = file_get_contents(self::getTestCertificatesDir() . DIRECTORY_SEPARATOR . 'TEST_of_EID-SK_2016.pem.crt');
        $issuerCert = $certWithoutOcsp;

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('OCSP responder URL');

        $checker->checkRevocationStatus($certWithoutOcsp, $issuerCert);
    }

    private static function getTestEndEntityCertsDir(): string
    {
        return dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'test_end_entity_certs';
    }

    private static function getCertPairWithOcspUrl(): array
    {
        $dir = self::getTestEndEntityCertsDir();
        $subjectCert = file_get_contents($dir . DIRECTORY_SEPARATOR . 'ocsp_ee.pem.crt');
        $issuerCert = file_get_contents($dir . DIRECTORY_SEPARATOR . 'test_ca.pem.crt');

        return [$subjectCert, $issuerCert];
    }

    #[Test]
    public function checkRevocationStatusThrowsOnHttpError(): void
    {
        $mock = new MockHandler([
            new Response(500, [], 'Internal Server Error'),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        $checker = new OcspCertificateRevocationChecker($client);

        [$subjectCert, $issuerCert] = self::getCertPairWithOcspUrl();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('OCSP request failed');

        $checker->checkRevocationStatus($subjectCert, $issuerCert);
    }

    #[Test]
    public function checkRevocationStatusThrowsOnWrongContentType(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'text/html'], 'not ocsp'),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        $checker = new OcspCertificateRevocationChecker($client);

        [$subjectCert, $issuerCert] = self::getCertPairWithOcspUrl();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('unexpected Content-Type');

        $checker->checkRevocationStatus($subjectCert, $issuerCert);
    }

    #[Test]
    public function checkRevocationStatusThrowsOnConnectionError(): void
    {
        $mock = new MockHandler([
            new \GuzzleHttp\Exception\ConnectException(
                'Connection refused',
                new \GuzzleHttp\Psr7\Request('POST', 'http://demo.sk.ee/ocsp'),
            ),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        $checker = new OcspCertificateRevocationChecker($client);

        [$subjectCert, $issuerCert] = self::getCertPairWithOcspUrl();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('OCSP request failed');

        $checker->checkRevocationStatus($subjectCert, $issuerCert);
    }

    #[Test]
    public function checkRevocationStatusThrowsOnInvalidOcspResponse(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/ocsp-response'], 'invalid-der-data'),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        $checker = new OcspCertificateRevocationChecker($client);

        [$subjectCert, $issuerCert] = self::getCertPairWithOcspUrl();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Failed to decode OCSP response');

        $checker->checkRevocationStatus($subjectCert, $issuerCert);
    }
}
