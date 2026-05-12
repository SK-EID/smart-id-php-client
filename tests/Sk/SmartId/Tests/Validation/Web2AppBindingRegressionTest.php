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
use Sk\SmartId\Util\Base64Url;
use Sk\SmartId\Validation\AuthenticationResponseValidator;
use Sk\SmartId\Validation\Web2AppCallbackBinding;

/**
 * Regression test for the Web2App/App2App callback-binding bypass.
 *
 * Original bug: AuthenticationResponseValidator::validate() did not
 * accept the callback-binding parameters and did not enforce them when
 * the response was for a web2app/app2app flow. The two binding checks
 * (verifySessionSecret, verifyUserChallenge) were public-but-manual,
 * and an integrator who only called validate() would accept any
 * leaked / replayed signed SessionStatus for the right RP.
 *
 * Fix: validate() now takes a ?Web2AppCallbackBinding. If the response's
 * flowType is web2app/app2app, the binding is required, and the two
 * checks are performed inside validate().
 */
class Web2AppBindingRegressionTest extends TestCase
{
    private const RP_CHALLENGE = 'dGVzdC1ycC1jaGFsbGVuZ2U=';
    private const RP_NAME = 'DEMO';
    private const INTERACTIONS_B64 = 'aW50ZXJhY3Rpb25z';
    private const SERVER_RANDOM = 'test-server-random';
    private const INTERACTION_TYPE_USED = 'displayTextAndPIN';

    private static function certsDir(): string
    {
        return dirname(__DIR__, 4) . '/resources/test_end_entity_certs';
    }

    private static function testCaPem(): string
    {
        return file_get_contents(self::certsDir() . '/test_ca.pem.crt');
    }

    /**
     * Build a real RSA-PSS-signed SessionStatus for a web2app flow,
     * binding `userChallenge = sha256(verifier)` and a given callback URL.
     */
    private static function buildSignedStatus(
        string $callbackUrl,
        string $userChallengeVerifier,
        string $flowType,
    ): SessionStatus {
        $certPem = file_get_contents(self::certsDir() . '/qualified_smartid_ee.pem.crt');
        $keyPem = file_get_contents(self::certsDir() . '/qualified_smartid_ee.key.pem');

        $certBase64 = str_replace(
            ['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', "\r", "\n", ' '],
            '',
            $certPem,
        );

        $userChallenge = Base64Url::encode(hash('sha256', $userChallengeVerifier, true));

        $payload = implode('|', [
            'smart-id',
            'ACSP_V2',
            self::SERVER_RANDOM,
            self::RP_CHALLENGE,
            $userChallenge,
            base64_encode(self::RP_NAME),
            base64_encode(''),
            base64_encode(hash('sha256', self::INTERACTIONS_B64, true)),
            self::INTERACTION_TYPE_USED,
            $callbackUrl,
            $flowType,
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
        $sigBytes = $rsaKey->sign($payload);

        return new SessionStatus(
            'COMPLETE',
            new SessionResult('OK', 'DOC123'),
            new SessionCertificate($certBase64, 'QUALIFIED'),
            new SessionSignature(
                base64_encode($sigBytes),
                'rsassa-pss',
                self::SERVER_RANDOM,
                $userChallenge,
                $flowType,
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

    private static function makeValidator(): AuthenticationResponseValidator
    {
        $v = new AuthenticationResponseValidator();
        $v->setTrustedCaCertificates([self::testCaPem()]);

        return $v;
    }

    /**
     * Regression: a Web2App-flow response without a callback binding
     * must be rejected by validate(). Integrators can no longer forget
     * the binding step.
     */
    #[Test]
    public function web2AppFlowWithoutBindingIsRejected(): void
    {
        $callbackUrl = 'https://rp.example/cb?value=' . Base64Url::encode(random_bytes(16));
        $verifier = bin2hex(random_bytes(32));
        $status = self::buildSignedStatus($callbackUrl, $verifier, 'web2app');

        $validator = self::makeValidator();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/web2app|callback binding|sessionSecret/i');

        $validator->validate(
            $status,
            self::RP_CHALLENGE,
            self::RP_NAME,
            self::INTERACTIONS_B64,
            $callbackUrl,
        );
    }

    /**
     * Regression: an App2App-flow response without a callback binding
     * must also be rejected.
     */
    #[Test]
    public function app2AppFlowWithoutBindingIsRejected(): void
    {
        $callbackUrl = 'https://rp.example/cb?value=' . Base64Url::encode(random_bytes(16));
        $verifier = bin2hex(random_bytes(32));
        $status = self::buildSignedStatus($callbackUrl, $verifier, 'app2app');

        $validator = self::makeValidator();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/app2app|callback binding|sessionSecret/i');

        $validator->validate(
            $status,
            self::RP_CHALLENGE,
            self::RP_NAME,
            self::INTERACTIONS_B64,
            $callbackUrl,
        );
    }

    /**
     * Regression: when the binding is supplied with the wrong
     * sessionSecret (attacker doesn't know it), validate() rejects.
     */
    #[Test]
    public function web2AppFlowWithWrongSessionSecretIsRejected(): void
    {
        $callbackUrl = 'https://rp.example/cb?value=' . Base64Url::encode(random_bytes(16));
        $verifier = bin2hex(random_bytes(32));
        $status = self::buildSignedStatus($callbackUrl, $verifier, 'web2app');

        // What the legit RP minted at session start
        $rpKnownSessionSecret = base64_encode(random_bytes(32));
        $expectedDigest = Base64Url::encode(hash('sha256', base64_decode($rpKnownSessionSecret), true));

        // Attacker presents wrong session secret
        $wrongSessionSecret = base64_encode(random_bytes(32));

        $binding = new Web2AppCallbackBinding(
            sessionSecret: $wrongSessionSecret,
            sessionSecretDigest: $expectedDigest,
            userChallengeVerifier: $verifier,
        );

        $validator = self::makeValidator();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('sessionSecretDigest');

        $validator->validate(
            $status,
            self::RP_CHALLENGE,
            self::RP_NAME,
            self::INTERACTIONS_B64,
            $callbackUrl,
            web2AppCallbackBinding: $binding,
        );
    }

    /**
     * Regression: when the binding is supplied with the wrong
     * userChallengeVerifier (attacker doesn't know it), validate() rejects.
     */
    #[Test]
    public function web2AppFlowWithWrongUserChallengeVerifierIsRejected(): void
    {
        $callbackUrl = 'https://rp.example/cb?value=' . Base64Url::encode(random_bytes(16));
        $realVerifier = bin2hex(random_bytes(32));
        $status = self::buildSignedStatus($callbackUrl, $realVerifier, 'web2app');

        $sessionSecret = base64_encode(random_bytes(32));
        $sessionSecretDigest = Base64Url::encode(hash('sha256', base64_decode($sessionSecret), true));

        $binding = new Web2AppCallbackBinding(
            sessionSecret: $sessionSecret,
            sessionSecretDigest: $sessionSecretDigest,
            userChallengeVerifier: 'attacker-guess', // wrong
        );

        $validator = self::makeValidator();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('userChallenge');

        $validator->validate(
            $status,
            self::RP_CHALLENGE,
            self::RP_NAME,
            self::INTERACTIONS_B64,
            $callbackUrl,
            web2AppCallbackBinding: $binding,
        );
    }

    /**
     * Positive control: when the binding is supplied with correct values,
     * a Web2App-flow response is accepted.
     */
    #[Test]
    public function web2AppFlowWithCorrectBindingIsAccepted(): void
    {
        $callbackUrl = 'https://rp.example/cb?value=' . Base64Url::encode(random_bytes(16));
        $verifier = bin2hex(random_bytes(32));
        $status = self::buildSignedStatus($callbackUrl, $verifier, 'web2app');

        $sessionSecret = base64_encode(random_bytes(32));
        $sessionSecretDigest = Base64Url::encode(hash('sha256', base64_decode($sessionSecret), true));

        $binding = new Web2AppCallbackBinding(
            sessionSecret: $sessionSecret,
            sessionSecretDigest: $sessionSecretDigest,
            userChallengeVerifier: $verifier,
        );

        $validator = self::makeValidator();

        $identity = $validator->validate(
            $status,
            self::RP_CHALLENGE,
            self::RP_NAME,
            self::INTERACTIONS_B64,
            $callbackUrl,
            web2AppCallbackBinding: $binding,
        );

        $this->assertSame('30303039914', $identity->getIdentityCode());
    }

    /**
     * Positive control: QR (non-Web2App) flows must continue to work
     * without a binding — the binding is only mandatory for web2app/app2app.
     */
    #[Test]
    public function qrFlowDoesNotRequireBinding(): void
    {
        $verifier = bin2hex(random_bytes(32));
        $status = self::buildSignedStatus('', $verifier, 'qr');

        $validator = self::makeValidator();

        $identity = $validator->validate(
            $status,
            self::RP_CHALLENGE,
            self::RP_NAME,
            self::INTERACTIONS_B64,
        );

        $this->assertSame('30303039914', $identity->getIdentityCode());
    }
}
