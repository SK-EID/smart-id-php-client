<?php

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
     * @param string|null $initialCallbackUrl Optional callback URL (for Web2App/App2App flows)
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
