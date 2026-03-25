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
use GuzzleHttp\Psr7\HttpFactory;
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

    private static function createChecker(
        Client $client,
        ?string $ocspUrlOverride = null,
        ?string $designatedResponderCertPem = null,
    ): OcspCertificateRevocationChecker {
        $factory = new HttpFactory();

        return new OcspCertificateRevocationChecker(
            $client,
            $factory,
            $factory,
            $ocspUrlOverride,
            $designatedResponderCertPem,
        );
    }

    #[Test]
    public function constructorAcceptsPsr18Client(): void
    {
        $checker = self::createChecker(new Client());
        $this->assertInstanceOf(OcspCertificateRevocationChecker::class, $checker);
    }

    #[Test]
    public function checkRevocationStatusThrowsForCertWithoutOcspUrl(): void
    {
        $checker = self::createChecker(new Client());

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
        $checker = self::createChecker($client);

        [$subjectCert, $issuerCert] = self::getCertPairWithOcspUrl();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('OCSP responder returned HTTP status 500');

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
        $checker = self::createChecker($client);

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
        $checker = self::createChecker($client);

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
        $checker = self::createChecker($client);

        [$subjectCert, $issuerCert] = self::getCertPairWithOcspUrl();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Failed to decode OCSP response');

        $checker->checkRevocationStatus($subjectCert, $issuerCert);
    }

    #[Test]
    public function checkRevocationStatusPassesWithValidSignedOcspResponse(): void
    {
        $ocspResponseDer = file_get_contents(self::getTestEndEntityCertsDir() . DIRECTORY_SEPARATOR . 'ocsp_response_good.der');

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/ocsp-response'], $ocspResponseDer),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        $checker = self::createChecker($client);

        [$subjectCert, $issuerCert] = self::getCertPairWithOcspUrl();

        // Should not throw — valid signature, good cert status
        $checker->checkRevocationStatus($subjectCert, $issuerCert);
        $this->assertTrue(true);
    }

    #[Test]
    public function checkRevocationStatusThrowsOnTamperedSignature(): void
    {
        $ocspResponseDer = file_get_contents(self::getTestEndEntityCertsDir() . DIRECTORY_SEPARATOR . 'ocsp_response_bad_sig.der');

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/ocsp-response'], $ocspResponseDer),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        $checker = self::createChecker($client);

        [$subjectCert, $issuerCert] = self::getCertPairWithOcspUrl();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('OCSP response signature verification failed');

        $checker->checkRevocationStatus($subjectCert, $issuerCert);
    }

    #[Test]
    public function checkRevocationStatusThrowsOnRevokedCertificate(): void
    {
        $ocspResponseDer = file_get_contents(self::getTestEndEntityCertsDir() . DIRECTORY_SEPARATOR . 'ocsp_response_revoked.der');

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/ocsp-response'], $ocspResponseDer),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        $checker = self::createChecker($client);

        [$subjectCert, $issuerCert] = self::getCertPairWithOcspUrl();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Certificate has been revoked');

        $checker->checkRevocationStatus($subjectCert, $issuerCert);
    }

    #[Test]
    public function checkRevocationStatusPassesWithDesignatedResponderCert(): void
    {
        $ocspResponseDer = file_get_contents(self::getTestEndEntityCertsDir() . DIRECTORY_SEPARATOR . 'ocsp_response_good.der');
        $responderCertPem = file_get_contents(self::getTestEndEntityCertsDir() . DIRECTORY_SEPARATOR . 'ocsp_responder.pem.crt');

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/ocsp-response'], $ocspResponseDer),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        $checker = self::createChecker($client, designatedResponderCertPem: $responderCertPem);

        [$subjectCert, $issuerCert] = self::getCertPairWithOcspUrl();

        // Should not throw — pinned cert matches the embedded responder cert
        $checker->checkRevocationStatus($subjectCert, $issuerCert);
        $this->assertTrue(true);
    }

    #[Test]
    public function checkRevocationStatusThrowsWhenDesignatedResponderCertDoesNotMatch(): void
    {
        $ocspResponseDer = file_get_contents(self::getTestEndEntityCertsDir() . DIRECTORY_SEPARATOR . 'ocsp_response_good.der');
        // Use the CA cert as the "wrong" designated responder cert
        $wrongCertPem = file_get_contents(self::getTestEndEntityCertsDir() . DIRECTORY_SEPARATOR . 'test_ca.pem.crt');

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/ocsp-response'], $ocspResponseDer),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        $checker = self::createChecker($client, designatedResponderCertPem: $wrongCertPem);

        [$subjectCert, $issuerCert] = self::getCertPairWithOcspUrl();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('does not match the designated responder certificate');

        $checker->checkRevocationStatus($subjectCert, $issuerCert);
    }

    #[Test]
    public function createReturnsInstance(): void
    {
        $checker = OcspCertificateRevocationChecker::create();
        $this->assertInstanceOf(OcspCertificateRevocationChecker::class, $checker);
    }

    #[Test]
    public function createDesignatedReturnsInstance(): void
    {
        $checker = OcspCertificateRevocationChecker::createDesignated(
            'http://ocsp.example.com',
            '-----BEGIN CERTIFICATE-----TEST-----END CERTIFICATE-----',
        );
        $this->assertInstanceOf(OcspCertificateRevocationChecker::class, $checker);
    }

    #[Test]
    public function checkRevocationStatusUsesOcspUrlOverride(): void
    {
        $ocspResponseDer = file_get_contents(self::getTestEndEntityCertsDir() . DIRECTORY_SEPARATOR . 'ocsp_response_good.der');

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/ocsp-response'], $ocspResponseDer),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        // Override the OCSP URL - the mock will handle it regardless of URL
        $checker = self::createChecker($client, ocspUrlOverride: 'http://custom-ocsp.example.com/ocsp');

        [$subjectCert, $issuerCert] = self::getCertPairWithOcspUrl();

        // Should not throw — uses the override URL and gets a valid response
        $checker->checkRevocationStatus($subjectCert, $issuerCert);
        $this->assertTrue(true);
    }

    #[Test]
    public function checkRevocationStatusThrowsForOcspResponseStatusMalformedRequest(): void
    {
        // Build an OCSP response with malformedRequest status (1)
        $responseStatus = "\x0A\x01\x01"; // ENUMERATED 1 (malformedRequest)
        $ocspResponse = "\x30" . chr(strlen($responseStatus)) . $responseStatus;

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/ocsp-response'], $ocspResponse),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        $checker = self::createChecker($client);

        [$subjectCert, $issuerCert] = self::getCertPairWithOcspUrl();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('malformed request');

        $checker->checkRevocationStatus($subjectCert, $issuerCert);
    }

    #[Test]
    public function checkRevocationStatusThrowsForOcspResponseStatusInternalError(): void
    {
        // Build an OCSP response with internalError status (2)
        $responseStatus = "\x0A\x01\x02"; // ENUMERATED 2 (internalError)
        $ocspResponse = "\x30" . chr(strlen($responseStatus)) . $responseStatus;

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/ocsp-response'], $ocspResponse),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        $checker = self::createChecker($client);

        [$subjectCert, $issuerCert] = self::getCertPairWithOcspUrl();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('internal error');

        $checker->checkRevocationStatus($subjectCert, $issuerCert);
    }

    #[Test]
    public function checkRevocationStatusThrowsForOcspResponseStatusTryLater(): void
    {
        // Build an OCSP response with tryLater status (3)
        $responseStatus = "\x0A\x01\x03"; // ENUMERATED 3 (tryLater)
        $ocspResponse = "\x30" . chr(strlen($responseStatus)) . $responseStatus;

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/ocsp-response'], $ocspResponse),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        $checker = self::createChecker($client);

        [$subjectCert, $issuerCert] = self::getCertPairWithOcspUrl();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('try later');

        $checker->checkRevocationStatus($subjectCert, $issuerCert);
    }

    #[Test]
    public function checkRevocationStatusThrowsForOcspResponseStatusSigRequired(): void
    {
        // Build an OCSP response with sigRequired status (5)
        $responseStatus = "\x0A\x01\x05"; // ENUMERATED 5 (sigRequired)
        $ocspResponse = "\x30" . chr(strlen($responseStatus)) . $responseStatus;

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/ocsp-response'], $ocspResponse),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        $checker = self::createChecker($client);

        [$subjectCert, $issuerCert] = self::getCertPairWithOcspUrl();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('signature required');

        $checker->checkRevocationStatus($subjectCert, $issuerCert);
    }

    #[Test]
    public function checkRevocationStatusThrowsForOcspResponseStatusUnauthorized(): void
    {
        // Build an OCSP response with unauthorized status (6)
        $responseStatus = "\x0A\x01\x06"; // ENUMERATED 6 (unauthorized)
        $ocspResponse = "\x30" . chr(strlen($responseStatus)) . $responseStatus;

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/ocsp-response'], $ocspResponse),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        $checker = self::createChecker($client);

        [$subjectCert, $issuerCert] = self::getCertPairWithOcspUrl();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('unauthorized');

        $checker->checkRevocationStatus($subjectCert, $issuerCert);
    }

    #[Test]
    public function checkRevocationStatusThrowsForEmptyOcspResponse(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/ocsp-response'], ''),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        $checker = self::createChecker($client);

        [$subjectCert, $issuerCert] = self::getCertPairWithOcspUrl();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Failed to decode OCSP response');

        $checker->checkRevocationStatus($subjectCert, $issuerCert);
    }

    #[Test]
    public function checkRevocationStatusWithCertWithoutOcspUrlButUrlOverride(): void
    {
        $ocspResponseDer = file_get_contents(self::getTestEndEntityCertsDir() . DIRECTORY_SEPARATOR . 'ocsp_response_good.der');

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/ocsp-response'], $ocspResponseDer),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        $checker = self::createChecker($client, ocspUrlOverride: 'http://ocsp.example.com');

        // Use the ocsp_ee cert which has an OCSP URL, but the override URL should be used
        [$subjectCert, $issuerCert] = self::getCertPairWithOcspUrl();

        $checker->checkRevocationStatus($subjectCert, $issuerCert);
        $this->assertTrue(true);
    }

    #[Test]
    public function checkRevocationStatusPassesWithCaSignedResponse(): void
    {
        $ocspResponseDer = file_get_contents(self::getTestEndEntityCertsDir() . DIRECTORY_SEPARATOR . 'ocsp_response_ca_signed.der');

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/ocsp-response'], $ocspResponseDer),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        $checker = self::createChecker($client);

        [$subjectCert, $issuerCert] = self::getCertPairWithOcspUrl();

        // Should not throw — CA is the responder (no embedded certs), signature verified against issuer
        $checker->checkRevocationStatus($subjectCert, $issuerCert);
        $this->assertTrue(true);
    }

    #[Test]
    public function checkRevocationStatusThrowsWhenResponseCertIdDoesNotMatchRequestedCertificate(): void
    {
        $ocspResponseDer = file_get_contents(self::getTestEndEntityCertsDir() . DIRECTORY_SEPARATOR . 'ocsp_response_wrong_cert.der');

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/ocsp-response'], $ocspResponseDer),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        $checker = self::createChecker($client);

        [$subjectCert, $issuerCert] = self::getCertPairWithOcspUrl();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('serialNumber does not match the requested certificate');

        $checker->checkRevocationStatus($subjectCert, $issuerCert);
    }

    #[Test]
    public function checkRevocationStatusThrowsForUnknownCertStatus(): void
    {
        $ocspResponseDer = file_get_contents(self::getTestEndEntityCertsDir() . DIRECTORY_SEPARATOR . 'ocsp_response_unknown.der');

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/ocsp-response'], $ocspResponseDer),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        $checker = self::createChecker($client);

        [$subjectCert, $issuerCert] = self::getCertPairWithOcspUrl();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('unknown');

        $checker->checkRevocationStatus($subjectCert, $issuerCert);
    }
}
