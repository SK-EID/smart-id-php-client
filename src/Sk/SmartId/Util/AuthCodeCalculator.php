<?php

declare(strict_types=1);

namespace Sk\SmartId\Util;

use Sk\SmartId\Enum\SignatureProtocol;
use Sk\SmartId\Model\Interaction;

class AuthCodeCalculator
{
    private const SCHEME_NAME = 'smartid';

    /**
     * Calculate the authCode for Device Link authentication.
     *
     * @param string $sessionSecret Base64-encoded session secret from API response
     * @param string $rpChallenge Base64-encoded RP challenge
     * @param string $rpName Relying Party name
     * @param Interaction[] $interactions Array of interaction objects
     * @param int $elapsedSeconds Seconds elapsed since session start (for dynamic QR)
     * @param string|null $callbackUrl Optional callback URL
     * @param string|null $brokeredRpName Optional brokered RP name
     * @param bool $unprotectedLink Whether link is unprotected
     */
    public static function calculate(
        string $sessionSecret,
        string $rpChallenge,
        string $rpName,
        array $interactions,
        int $elapsedSeconds = 0,
        ?string $callbackUrl = null,
        ?string $brokeredRpName = null,
        bool $unprotectedLink = false,
    ): string {
        $decodedSecret = base64_decode($sessionSecret, true);
        if ($decodedSecret === false) {
            throw new \InvalidArgumentException('Invalid base64 encoded sessionSecret');
        }

        $payload = self::buildPayload(
            $rpChallenge,
            $rpName,
            $interactions,
            $elapsedSeconds,
            $callbackUrl,
            $brokeredRpName,
            $unprotectedLink,
        );

        $hmac = hash_hmac('sha256', $payload, $decodedSecret, true);

        return self::base64UrlEncode($hmac);
    }

    /**
     * @param Interaction[] $interactions
     */
    private static function buildPayload(
        string $rpChallenge,
        string $rpName,
        array $interactions,
        int $elapsedSeconds,
        ?string $callbackUrl,
        ?string $brokeredRpName,
        bool $unprotectedLink,
    ): string {
        $rpNameBase64 = base64_encode($rpName);
        $brokeredRpNameBase64 = $brokeredRpName !== null ? base64_encode($brokeredRpName) : '';
        $interactionsString = self::formatInteractions($interactions);
        $callbackUrlValue = $callbackUrl ?? '';
        $unprotectedLinkValue = $unprotectedLink ? 'true' : 'false';

        return implode('|', [
            self::SCHEME_NAME,
            SignatureProtocol::ACSP_V2->value,
            $rpChallenge,
            (string) $elapsedSeconds,
            $rpNameBase64,
            $brokeredRpNameBase64,
            $interactionsString,
            $callbackUrlValue,
            $unprotectedLinkValue,
        ]);
    }

    /**
     * @param Interaction[] $interactions
     */
    private static function formatInteractions(array $interactions): string
    {
        if (empty($interactions)) {
            return '';
        }

        $formatted = array_map(
            fn (Interaction $interaction) => $interaction->toPayloadString(),
            $interactions,
        );

        return implode(',', $formatted);
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
