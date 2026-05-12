<?php

declare(strict_types=1);

namespace Sk\SmartId\Tests\Validation;

use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\RSA\PrivateKey as RSAPrivateKey;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\Exception\ValidationException;
use Sk\SmartId\Session\SessionCertificate;
use Sk\SmartId\Session\SessionResult;
use Sk\SmartId\Session\SessionSignature;
use Sk\SmartId\Session\SessionStatus;
use Sk\SmartId\Session\SignatureAlgorithmParameters;
use Sk\SmartId\Validation\AuthenticationResponseValidator;
use Sk\SmartId\Validation\TrustedCACertificateStore;

/**
 * Regression test for the trust-anchor mis-classification bypass.
 *
 * Original bug: phpseclib's X509::loadCA() does NOT enforce the RFC 5280
 * requirement that a trust anchor have basicConstraints CA:TRUE. The
 * Smart-ID validator passes any PEM the RP supplies straight into loadCA().
 * As a result, if an RP ever loads an end-entity cert (CA:FALSE) into its
 * trust store — by misconfiguration, supply-chain tampering of the trust
 * bundle, or any operational mistake — that cert silently becomes a CA,
 * and whoever holds its private key can self-issue Smart-ID-shaped
 * certificates that validate() will accept.
 *
 * Fix: the trust-anchor boundary (TrustedCACertificateStore and
 * AuthenticationResponseValidator) rejects certificates that lack
 * basicConstraints CA:TRUE or keyUsage keyCertSign.
 */
class TrustAnchorNonCaRejectionRegressionTest extends TestCase
{
    private const RP_CHALLENGE = 'dGVzdC1ycC1jaGFsbGVuZ2U=';
    private const RP_NAME = 'DEMO';
    private const INTERACTIONS_B64 = 'aW50ZXJhY3Rpb25z';
    private const SERVER_RANDOM = 'test-server-random';
    private const INTERACTION_TYPE_USED = 'displayTextAndPIN';

    /**
     * @return array{0:string,1:string} [pem, keyPem]
     */
    private static function generateSelfSignedNonCaCert(): array
    {
        $cnf = tempnam(sys_get_temp_dir(), 'sscnf_');
        file_put_contents($cnf, <<<'CFG'
[req]
default_bits = 2048
prompt = no
distinguished_name = dn
req_extensions = v3_ee
x509_extensions = v3_ee
[dn]
CN = SELFSIGNED ATTACKER, PNOEE-99999999999
GN = SELFSIGNED
SN = ATTACKER
serialNumber = PNOEE-99999999999
C = EE
[v3_ee]
basicConstraints = critical, CA:FALSE
keyUsage = critical, digitalSignature
extendedKeyUsage = clientAuth
certificatePolicies = 1.3.6.1.4.1.10015.17.2, 0.4.0.2042.1.2
subjectKeyIdentifier = hash
CFG);

        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $csr = openssl_csr_new(
            ['commonName' => 'SELFSIGNED ATTACKER'],
            $key,
            ['config' => $cnf, 'req_extensions' => 'v3_ee'],
        );
        $x509 = openssl_csr_sign($csr, null, $key, 365, ['config' => $cnf, 'x509_extensions' => 'v3_ee']);
        openssl_x509_export($x509, $pem);
        openssl_pkey_export($key, $keyPem);
        unlink($cnf);

        return [$pem, $keyPem];
    }

    private static function buildSignedStatus(string $eeCertPem, string $eeKeyPem): SessionStatus
    {
        $eeCertBase64 = str_replace(
            ['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', "\r", "\n", ' '],
            '',
            $eeCertPem,
        );

        $payload = implode('|', [
            'smart-id',
            'ACSP_V2',
            self::SERVER_RANDOM,
            self::RP_CHALLENGE,
            'user-challenge',
            base64_encode(self::RP_NAME),
            base64_encode(''),
            base64_encode(hash('sha256', self::INTERACTIONS_B64, true)),
            self::INTERACTION_TYPE_USED,
            '',
            'qr',
        ]);

        $loaded = PublicKeyLoader::load($eeKeyPem);
        assert($loaded instanceof RSAPrivateKey);

        // @phpstan-ignore-next-line
        $rsaKey = $loaded
            ->withHash('sha256')
            ->withMGFHash('sha256')
            ->withPadding(RSA::SIGNATURE_PSS)
            ->withSaltLength(32);

        // @phpstan-ignore method.nonObject
        $sigBytes = $rsaKey->sign($payload);

        return new SessionStatus(
            'COMPLETE',
            new SessionResult('OK', 'DOC123'),
            new SessionCertificate($eeCertBase64, 'QUALIFIED'),
            new SessionSignature(
                base64_encode($sigBytes),
                'rsassa-pss',
                self::SERVER_RANDOM,
                'user-challenge',
                'qr',
                parsedAlgorithmParameters: new SignatureAlgorithmParameters(
                    hashAlgorithm: 'SHA-256',
                    saltLength: 32,
                    maskGenAlgorithm: 'id-mgf1',
                    maskGenHashAlgorithm: 'SHA-256',
                ),
            ),
            'ACSP_V2',
            interactionTypeUsed: self::INTERACTION_TYPE_USED,
        );
    }

    /**
     * Regression: a self-signed end-entity certificate (CA:FALSE) installed
     * as a trust anchor on the validator must be rejected. Pre-fix, this
     * trust-anchor mis-classification accepted any cert signed by the same
     * key as a "valid Smart-ID authentication".
     */
    #[Test]
    public function validatorRejectsNonCaCertificateInTrustStore(): void
    {
        [$selfSigned, $selfSignedKey] = self::generateSelfSignedNonCaCert();
        $status = self::buildSignedStatus($selfSigned, $selfSignedKey);

        $validator = new AuthenticationResponseValidator();
        $validator->setTrustedCaCertificates([$selfSigned]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/CA:TRUE|not a CA|keyCertSign|trust anchor/i');

        $validator->validate(
            $status,
            self::RP_CHALLENGE,
            self::RP_NAME,
            self::INTERACTIONS_B64,
        );
    }

    /**
     * Regression: TrustedCACertificateStore::addCertificate() must also
     * reject a non-CA cert at the boundary, so the misconfiguration is
     * caught at load time rather than at validation time.
     */
    #[Test]
    public function trustStoreRejectsNonCaCertificateAtAddTime(): void
    {
        [$selfSigned, ] = self::generateSelfSignedNonCaCert();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/CA:TRUE|not a CA|keyCertSign|trust anchor/i');

        TrustedCACertificateStore::create()->addCertificate($selfSigned);
    }

    /**
     * Positive control: a legitimate CA cert (the test_ca shipped with
     * the SDK tests) must continue to load.
     */
    #[Test]
    public function trustStoreAcceptsLegitimateCaCertificate(): void
    {
        $testCaPem = file_get_contents(
            dirname(__DIR__, 4) . '/resources/test_end_entity_certs/test_ca.pem.crt',
        );

        $store = TrustedCACertificateStore::create()->addCertificate($testCaPem);
        $this->assertCount(1, $store->getCertificates());
    }
}
