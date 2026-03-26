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

namespace Sk\SmartId\Util;

use Sk\SmartId\Enum\SchemeName;
use Sk\SmartId\Enum\SignatureProtocol;
use Sk\SmartId\Model\AbstractInteraction;

class AuthCodeCalculator
{
    /**
     * Calculate the authCode for Device Link authentication.
     *
     * @param string $sessionSecret Base64-encoded session secret from API response
     * @param string $rpChallenge Base64-encoded RP challenge
     * @param string $rpName Relying Party name
     * @param string $interactionsBase64 Base64-encoded JSON string of interactions
     * @param string $unprotectedDeviceLink The device link URL without authCode parameter
     * @param string|null $initialCallbackUrl Optional callback URL (for Web2App flows)
     * @param string|null $brokeredRpName Optional brokered RP name
     * @param SchemeName $schemeName Scheme name for the target environment
     */
    public static function calculate(
        string $sessionSecret,
        string $rpChallenge,
        string $rpName,
        string $interactionsBase64,
        string $unprotectedDeviceLink,
        ?string $initialCallbackUrl = null,
        ?string $brokeredRpName = null,
        SchemeName $schemeName = SchemeName::PRODUCTION,
    ): string {
        $decodedSecret = base64_decode($sessionSecret, true);
        if ($decodedSecret === false) {
            throw new \InvalidArgumentException('Invalid base64 encoded sessionSecret');
        }

        $payload = self::buildPayload(
            $schemeName->value,
            $rpChallenge,
            $rpName,
            $interactionsBase64,
            $initialCallbackUrl,
            $brokeredRpName,
            $unprotectedDeviceLink,
        );

        $hmac = hash_hmac('sha256', $payload, $decodedSecret, true);

        return Base64Url::encode($hmac);
    }

    /**
     * @param string $interactionsBase64 Base64-encoded JSON string of interactions
     */
    private static function buildPayload(
        string $schemeName,
        string $rpChallenge,
        string $rpName,
        string $interactionsBase64,
        ?string $initialCallbackUrl,
        ?string $brokeredRpName,
        string $unprotectedDeviceLink,
    ): string {
        $rpNameBase64 = base64_encode($rpName);
        $brokeredRpNameBase64 = $brokeredRpName !== null ? base64_encode($brokeredRpName) : '';
        $callbackUrlValue = $initialCallbackUrl ?? '';

        return implode('|', [
            $schemeName,
            SignatureProtocol::ACSP_V2->value,
            $rpChallenge,
            $rpNameBase64,
            $brokeredRpNameBase64,
            $interactionsBase64,
            $callbackUrlValue,
            $unprotectedDeviceLink,
        ]);
    }
}
