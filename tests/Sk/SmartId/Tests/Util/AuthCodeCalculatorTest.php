<?php

declare(strict_types=1);

namespace Sk\SmartId\Tests\Util;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\Model\Interaction;
use Sk\SmartId\Util\AuthCodeCalculator;

class AuthCodeCalculatorTest extends TestCase
{
    #[Test]
    public function calculateReturnsBase64UrlEncodedString(): void
    {
        $sessionSecret = base64_encode(random_bytes(32));
        $rpChallenge = base64_encode(random_bytes(32));
        $rpName = 'Test RP';
        $interactions = [Interaction::verificationCodeChoice()];

        $authCode = AuthCodeCalculator::calculate(
            $sessionSecret,
            $rpChallenge,
            $rpName,
            $interactions,
        );

        $this->assertNotEmpty($authCode);
        // Base64URL should not contain + / or =
        $this->assertStringNotContainsString('+', $authCode);
        $this->assertStringNotContainsString('/', $authCode);
        $this->assertStringNotContainsString('=', $authCode);
    }

    #[Test]
    public function calculateWithUnprotectedDeviceLink(): void
    {
        $sessionSecret = base64_encode(random_bytes(32));
        $rpChallenge = base64_encode(random_bytes(32));
        $rpName = 'Test RP';
        $interactions = [Interaction::verificationCodeChoice()];

        $authCodeWithLink = AuthCodeCalculator::calculate(
            $sessionSecret,
            $rpChallenge,
            $rpName,
            $interactions,
            null,
            null,
            'https://smart-id.com?deviceLinkType=QR&elapsedSeconds=0&sessionToken=abc',
        );

        $authCodeWithDifferentLink = AuthCodeCalculator::calculate(
            $sessionSecret,
            $rpChallenge,
            $rpName,
            $interactions,
            null,
            null,
            'https://smart-id.com?deviceLinkType=QR&elapsedSeconds=5&sessionToken=abc',
        );

        $this->assertNotSame($authCodeWithLink, $authCodeWithDifferentLink);
    }

    #[Test]
    public function calculateWithCallbackUrl(): void
    {
        $sessionSecret = base64_encode(random_bytes(32));
        $rpChallenge = base64_encode(random_bytes(32));
        $rpName = 'Test RP';
        $interactions = [Interaction::verificationCodeChoice()];

        $authCodeWithCallback = AuthCodeCalculator::calculate(
            $sessionSecret,
            $rpChallenge,
            $rpName,
            $interactions,
            'https://example.com/callback',
        );

        $authCodeWithoutCallback = AuthCodeCalculator::calculate(
            $sessionSecret,
            $rpChallenge,
            $rpName,
            $interactions,
            null,
        );

        $this->assertNotSame($authCodeWithCallback, $authCodeWithoutCallback);
    }

    #[Test]
    public function calculateWithBrokeredRpName(): void
    {
        $sessionSecret = base64_encode(random_bytes(32));
        $rpChallenge = base64_encode(random_bytes(32));
        $rpName = 'Test RP';
        $interactions = [Interaction::verificationCodeChoice()];

        $authCodeWithBrokered = AuthCodeCalculator::calculate(
            $sessionSecret,
            $rpChallenge,
            $rpName,
            $interactions,
            null,
            'Brokered RP',
        );

        $authCodeWithoutBrokered = AuthCodeCalculator::calculate(
            $sessionSecret,
            $rpChallenge,
            $rpName,
            $interactions,
            null,
            null,
        );

        $this->assertNotSame($authCodeWithBrokered, $authCodeWithoutBrokered);
    }

    #[Test]
    public function calculateWithDifferentDeviceLinks(): void
    {
        $sessionSecret = base64_encode(random_bytes(32));
        $rpChallenge = base64_encode(random_bytes(32));
        $rpName = 'Test RP';
        $interactions = [Interaction::verificationCodeChoice()];

        $authCode1 = AuthCodeCalculator::calculate(
            $sessionSecret,
            $rpChallenge,
            $rpName,
            $interactions,
            null,
            null,
            'https://smart-id.com?deviceLinkType=QR&sessionToken=abc',
        );

        $authCode2 = AuthCodeCalculator::calculate(
            $sessionSecret,
            $rpChallenge,
            $rpName,
            $interactions,
            null,
            null,
            'https://smart-id.com?deviceLinkType=Web2App&sessionToken=abc',
        );

        $this->assertNotSame($authCode1, $authCode2);
    }

    #[Test]
    public function calculateWithMultipleInteractions(): void
    {
        $sessionSecret = base64_encode(random_bytes(32));
        $rpChallenge = base64_encode(random_bytes(32));
        $rpName = 'Test RP';

        $interactions1 = [Interaction::verificationCodeChoice()];
        $interactions2 = [
            Interaction::displayTextAndPin('Please confirm'),
            Interaction::verificationCodeChoice(),
        ];

        $authCode1 = AuthCodeCalculator::calculate(
            $sessionSecret,
            $rpChallenge,
            $rpName,
            $interactions1,
        );

        $authCode2 = AuthCodeCalculator::calculate(
            $sessionSecret,
            $rpChallenge,
            $rpName,
            $interactions2,
        );

        $this->assertNotSame($authCode1, $authCode2);
    }

    #[Test]
    public function calculateThrowsOnInvalidSessionSecret(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid base64 encoded sessionSecret');

        AuthCodeCalculator::calculate(
            'not-valid-base64!!!',
            base64_encode(random_bytes(32)),
            'Test RP',
            [Interaction::verificationCodeChoice()],
        );
    }

    #[Test]
    public function calculateWithEmptyInteractions(): void
    {
        $sessionSecret = base64_encode(random_bytes(32));
        $rpChallenge = base64_encode(random_bytes(32));
        $rpName = 'Test RP';

        $authCode = AuthCodeCalculator::calculate(
            $sessionSecret,
            $rpChallenge,
            $rpName,
            [],
        );

        $this->assertNotEmpty($authCode);
    }

    #[Test]
    public function calculateProducesConsistentResults(): void
    {
        $sessionSecret = base64_encode('fixed-secret-for-testing-32bytes');
        $rpChallenge = base64_encode('fixed-challenge-for-testing!!!!');
        $rpName = 'Test RP';
        $interactions = [Interaction::verificationCodeChoice()];
        $unprotectedDeviceLink = 'https://smart-id.com?deviceLinkType=QR&sessionToken=test';

        $authCode1 = AuthCodeCalculator::calculate(
            $sessionSecret,
            $rpChallenge,
            $rpName,
            $interactions,
            null,
            null,
            $unprotectedDeviceLink,
        );

        $authCode2 = AuthCodeCalculator::calculate(
            $sessionSecret,
            $rpChallenge,
            $rpName,
            $interactions,
            null,
            null,
            $unprotectedDeviceLink,
        );

        $this->assertSame($authCode1, $authCode2);
    }
}
