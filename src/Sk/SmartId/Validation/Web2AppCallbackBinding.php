<?php

declare(strict_types=1);

namespace Sk\SmartId\Validation;

/**
 * Carries the per-session callback-binding values that prove a Web2App or
 * App2App callback originated from the legitimate Smart-ID flow the RP
 * minted, and that the browser session presenting the callback is the
 * one that initiated it.
 *
 * - $sessionSecret: the Base64-encoded sessionSecret the RP received in
 *   the initial session response and stored against the session row.
 * - $sessionSecretDigest: the digest delivered back to the RP in the
 *   callback URL query string. Proves the callback came from the legit
 *   Smart-ID flow.
 * - $userChallengeVerifier: the PKCE-style verifier the RP minted at
 *   session start and stored in the browser session cookie / server
 *   session. Proves the browser presenting the callback is the one
 *   that initiated the flow.
 *
 * Pass an instance to AuthenticationResponseValidator::validate() for
 * any web2app or app2app session. The validator will refuse to validate
 * those flows without it.
 */
final readonly class Web2AppCallbackBinding
{
    public function __construct(
        public string $sessionSecret,
        public string $sessionSecretDigest,
        public string $userChallengeVerifier,
    ) {
    }
}
