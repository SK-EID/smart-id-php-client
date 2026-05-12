<?php

declare(strict_types=1);

namespace Sk\SmartId\Tests\Validation;

use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\RSA\PrivateKey as RSAPrivateKey;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\Enum\CertificateLevel;
use Sk\SmartId\Exception\ValidationException;
use Sk\SmartId\Session\SessionCertificate;
use Sk\SmartId\Session\SessionResult;
use Sk\SmartId\Session\SessionSignature;
use Sk\SmartId\Session\SessionStatus;
use Sk\SmartId\Session\SignatureAlgorithmParameters;
use Sk\SmartId\Validation\AuthenticationResponseValidator;

/**
 * Regression test for the certificate-level downgrade bypass.
 *
 * Original bug: AuthenticationResponseValidator::verifyCertificateLevel()
 * trusted the JSON-supplied SessionCertificate::certificateLevel string and
 * never cross-checked it against the X.509 certificatePolicies extension.
 * An attacker who could influence that JSON field could present a real,
 * properly-issued non-qualified Smart-ID certificate and have it accepted
 * by an RP that required CertificateLevel::QUALIFIED for eIDAS-grade
 * operations (qualified e-signature initiation, regulated onboarding, etc.).
 *
 * Fix: derive the level from the certificate's policy OIDs and compare
 * that — not the JSON string — against the requirement.
 */
class CertificateLevelDowngradeRegressionTest extends TestCase
{
    private const TEST_RP_CHALLENGE = 'dGVzdC1ycC1jaGFsbGVuZ2U=';
    private const TEST_RP_NAME = 'DEMO';
    private const TEST_INTERACTIONS_BASE64 = 'aW50ZXJhY3Rpb25z';
    private const TEST_SERVER_RANDOM = 'test-server-random';
    private const TEST_USER_CHALLENGE = 'test-user-challenge';
    private const TEST_FLOW_TYPE = 'qr';
    private const TEST_INTERACTION_TYPE_USED = 'displayTextAndPIN';

    private static function testCertsDir(): string
    {
        return dirname(__DIR__, 4) . '/resources/test_end_entity_certs';
    }

    private static function testCaPem(): string
    {
        return file_get_contents(self::testCertsDir() . '/test_ca.pem.crt');
    }

    private static function certBase64(string $name): string
    {
        $pem = file_get_contents(self::testCertsDir() . "/{$name}.pem.crt");
        $pem = str_replace(['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----'], '', $pem);

        return str_replace(["\r", "\n", ' '], '', $pem);
    }

    /**
     * Build a real RSA-PSS-signed SessionStatus.
     * The signature is genuine; the only "forgery" is that we lie about
     * the certificate level in the JSON envelope.
     */
    private static function buildSignedStatus(string $certBaseName, string $jsonCertificateLevel): SessionStatus
    {
        $eeCertBase64 = self::certBase64($certBaseName);
        $keyPem = file_get_contents(self::testCertsDir() . "/{$certBaseName}.key.pem");

        $interactionsHash = hash('sha256', self::TEST_INTERACTIONS_BASE64, true);
        $acspV2Payload = implode('|', [
            'smart-id',
            'ACSP_V2',
            self::TEST_SERVER_RANDOM,
            self::TEST_RP_CHALLENGE,
            self::TEST_USER_CHALLENGE,
            base64_encode(self::TEST_RP_NAME),
            base64_encode(''),
            base64_encode($interactionsHash),
            self::TEST_INTERACTION_TYPE_USED,
            '',
            self::TEST_FLOW_TYPE,
        ]);

        $loaded = PublicKeyLoader::load($keyPem);
        assert($loaded instanceof RSAPrivateKey);

        // @phpstan-ignore-next-line
        $rsaKey = $loaded
            ->withHash('sha256')
            ->withMGFHash('sha256')
            ->withPadding(RSA::SIGNATURE_PSS)
            ->withSaltLength(32);

        // @phpstan-ignore method.nonObject
        $signatureBytes = $rsaKey->sign($acspV2Payload);

        $signature = new SessionSignature(
            base64_encode($signatureBytes),
            'rsassa-pss',
            self::TEST_SERVER_RANDOM,
            self::TEST_USER_CHALLENGE,
            self::TEST_FLOW_TYPE,
            parsedAlgorithmParameters: new SignatureAlgorithmParameters(
                hashAlgorithm: 'SHA-256',
                saltLength: 32,
                maskGenAlgorithm: 'id-mgf1',
                maskGenHashAlgorithm: 'SHA-256',
            ),
        );

        return new SessionStatus(
            'COMPLETE',
            new SessionResult('OK', 'DOC123'),
            new SessionCertificate($eeCertBase64, $jsonCertificateLevel),
            $signature,
            'ACSP_V2',
            interactionTypeUsed: self::TEST_INTERACTION_TYPE_USED,
        );
    }

    /**
     * Sanity check: the non-qualified fixture really lacks the
     * qualified policy OID and the EU QCP OID.
     */
    #[Test]
    public function fixtureSanityNonQualifiedCertReallyLacksQualifiedPolicy(): void
    {
        $info = openssl_x509_parse(file_get_contents(self::testCertsDir() . '/non_qualified_smartid_ee.pem.crt'));
        $policies = $info['extensions']['certificatePolicies'] ?? '';

        $this->assertStringContainsString('1.3.6.1.4.1.10015.17.1', $policies);
        $this->assertStringNotContainsString('1.3.6.1.4.1.10015.17.2', $policies);
        $this->assertStringNotContainsString('0.4.0.2042.1.2', $policies);
    }

    /**
     * Regression: a non-qualified certificate must NOT be accepted when
     * QUALIFIED is required, even when the JSON envelope claims the level
     * is QUALIFIED. The cert's policy OIDs are the source of truth.
     */
    #[Test]
    public function nonQualifiedCertIsRejectedWhenQualifiedIsRequiredEvenIfJsonLies(): void
    {
        $status = self::buildSignedStatus(
            certBaseName: 'non_qualified_smartid_ee',
            jsonCertificateLevel: 'QUALIFIED', // attacker-controlled lie
        );

        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates([self::testCaPem()]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/qualified|policy|level/i');

        $validator->validate(
            $status,
            self::TEST_RP_CHALLENGE,
            self::TEST_RP_NAME,
            self::TEST_INTERACTIONS_BASE64,
            null,
            null,
            CertificateLevel::QUALIFIED,
        );
    }

    /**
     * Positive control: a genuine qualified cert must still pass when
     * QUALIFIED is required. (Guards against an over-broad fix.)
     */
    #[Test]
    public function qualifiedCertStillAcceptedWhenQualifiedRequired(): void
    {
        $status = self::buildSignedStatus(
            certBaseName: 'qualified_smartid_ee',
            jsonCertificateLevel: 'QUALIFIED',
        );

        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates([self::testCaPem()]);

        $identity = $validator->validate(
            $status,
            self::TEST_RP_CHALLENGE,
            self::TEST_RP_NAME,
            self::TEST_INTERACTIONS_BASE64,
            null,
            null,
            CertificateLevel::QUALIFIED,
        );

        $this->assertNotEmpty($identity->getIdentityCode());
    }
}
