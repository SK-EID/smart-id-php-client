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

use Sk\SmartId\Enum\SignatureProtocol;
use Sk\SmartId\Model\Interaction;

class AuthCodeCalculator
{
    public const SCHEME_NAME_PRODUCTION = 'smart-id';

    public const SCHEME_NAME_DEMO = 'smart-id-demo';

    /**
     * Calculate the authCode for Device Link authentication.
     *
     * @param string $sessionSecret Base64-encoded session secret from API response
     * @param string $rpChallenge Base64-encoded RP challenge
     * @param string $rpName Relying Party name
     * @param Interaction[] $interactions Array of interaction objects
     * @param string|null $initialCallbackUrl Optional callback URL (for Web2App flows)
     * @param string|null $brokeredRpName Optional brokered RP name
     * @param string $unprotectedDeviceLink The device link URL without authCode parameter
     * @param string $schemeName Scheme name ('smart-id' for production, 'smart-id-demo' for demo)
     */
    public static function calculate(
        string $sessionSecret,
        string $rpChallenge,
        string $rpName,
        array $interactions,
        ?string $initialCallbackUrl = null,
        ?string $brokeredRpName = null,
        string $unprotectedDeviceLink = '',
        string $schemeName = self::SCHEME_NAME_PRODUCTION,
    ): string {
        $decodedSecret = base64_decode($sessionSecret, true);
        if ($decodedSecret === false) {
            throw new \InvalidArgumentException('Invalid base64 encoded sessionSecret');
        }

        $payload = self::buildPayload(
            $schemeName,
            $rpChallenge,
            $rpName,
            $interactions,
            $initialCallbackUrl,
            $brokeredRpName,
            $unprotectedDeviceLink,
        );

        $hmac = hash_hmac('sha256', $payload, $decodedSecret, true);

        return self::base64UrlEncode($hmac);
    }

    /**
     * @param Interaction[] $interactions
     */
    private static function buildPayload(
        string $schemeName,
        string $rpChallenge,
        string $rpName,
        array $interactions,
        ?string $initialCallbackUrl,
        ?string $brokeredRpName,
        string $unprotectedDeviceLink,
    ): string {
        $rpNameBase64 = base64_encode($rpName);
        $brokeredRpNameBase64 = $brokeredRpName !== null ? base64_encode($brokeredRpName) : '';
        $interactionsBase64 = self::formatInteractions($interactions);
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

    /**
     * Format interactions as Base64-encoded JSON array.
     *
     * @param Interaction[] $interactions
     */
    private static function formatInteractions(array $interactions): string
    {
        if (empty($interactions)) {
            return '';
        }

        $interactionsArray = array_map(
            fn (Interaction $interaction) => $interaction->toArray(),
            $interactions,
        );

        return base64_encode(json_encode($interactionsArray, JSON_THROW_ON_ERROR));
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
