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

namespace Sk\SmartId\Tests\Util;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\DeviceLink\DeviceLinkInteraction;
use Sk\SmartId\Util\AuthCodeCalculator;

class AuthCodeCalculatorTest extends TestCase
{
    #[Test]
    public function calculateReturnsBase64UrlEncodedString(): void
    {
        $sessionSecret = base64_encode(random_bytes(32));
        $rpChallenge = base64_encode(random_bytes(32));
        $rpName = 'Test RP';
        $interactions = [DeviceLinkInteraction::displayTextAndPin('Test')];

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
        $interactions = [DeviceLinkInteraction::displayTextAndPin('Test')];

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
        $interactions = [DeviceLinkInteraction::displayTextAndPin('Test')];

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
        $interactions = [DeviceLinkInteraction::displayTextAndPin('Test')];

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
        $interactions = [DeviceLinkInteraction::displayTextAndPin('Test')];

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

        $interactions1 = [DeviceLinkInteraction::displayTextAndPin('Test')];
        $interactions2 = [
            DeviceLinkInteraction::displayTextAndPin('Please confirm'),
            DeviceLinkInteraction::displayTextAndPin('Test'),
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
            [DeviceLinkInteraction::displayTextAndPin('Test')],
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
        $interactions = [DeviceLinkInteraction::displayTextAndPin('Test')];
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
