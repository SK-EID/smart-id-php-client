# Smart-ID PHP client

This library supports Smart-ID API v3.

## Table of contents

- [Introduction](#introduction)
- [Features](#features)
- [Requirements](#requirements)
- [Getting the library](#getting-the-library)
- [Changelog](#changelog)
- [How to use it with RP API v3](#how-to-use-it-with-rp-api-v3)
    - [Test accounts for testing](#test-accounts-for-testing)
    - [Setting up SmartIdClient](#setting-up-smartidclient)
    - [Setting up the connector](#setting-up-the-connector)
        - [Setting up HTTPS public key pinning](#setting-up-https-public-key-pinning)
    - [Device link flows](#device-link-flows)
        - [Device link authentication session](#device-link-authentication-session)
            - [Using the request builder](#using-the-request-builder)
            - [Using request objects directly](#using-request-objects-directly)
        - [Generating QR code or device link](#generating-qr-code-or-device-link)
        - [Examples of allowed device link interactions](#examples-of-allowed-device-link-interactions)
    - [Notification-based flows](#notification-based-flows)
        - [Differences between notification-based and device link flows](#differences-between-notification-based-and-device-link-flows)
        - [Notification-based authentication session](#notification-based-authentication-session)
            - [With semantics identifier](#initiating-with-semantics-identifier)
            - [With document number](#initiating-with-document-number)
        - [Examples of allowed notification-based interactions](#examples-of-allowed-notification-based-interactions)
    - [Querying session status](#querying-session-status)
        - [Session status response](#session-status-response)
            - [End result values](#end-result-values)
            - [End result to exception mapping](#end-result-to-exception-mapping)
        - [Polling for final session status](#polling-for-final-session-status)
        - [Single status query](#single-status-query)
    - [Validating authentication response](#validating-authentication-response)
        - [Setting up trusted CA certificates](#setting-up-trusted-ca-certificates)
        - [Validating device link authentication](#validating-device-link-authentication)
        - [Validating notification-based authentication](#validating-notification-based-authentication)
        - [OCSP certificate revocation checking](#ocsp-certificate-revocation-checking)
        - [Web2App flow validation](#web2app-flow-validation)
        - [Validating callback URL with CallbackUrlUtil](#validating-callback-url-with-callbackurlutil)
    - [Extracting user identity](#extracting-user-identity)
    - [Additional request properties](#additional-request-properties)
        - [Requesting IP address of user's device](#requesting-ip-address-of-users-device)
    - [Exception handling](#exception-handling)

## Introduction

The Smart-ID PHP client can be used for easy integration of the [Smart-ID](https://www.smart-id.com) solution to information systems or e-services.

## Features

- User authentication (device link and notification-based flows)
- SmartIdClient facade for simplified integration
- Built-in HTTPS public key pinning for secure API communication
- QR code and Web2App device link URL generation
- ACSP_V2 signature protocol with RSA-PSS verification
- SHA-256, SHA-384, SHA-512, SHA3-256, SHA3-384, SHA3-512 hash algorithm support
- Certificate trust chain validation with full signatureAlgorithmParameters validation
- OCSP certificate revocation checking
- Verification code calculation
- CallbackUrlUtil for Web2App/App2App callback URL creation and validation

## Requirements

- PHP >= 8.4
- ext-openssl
- ext-json
- ext-curl (for HTTPS pinning via `CURLOPT_PINNEDPUBLICKEY`)

## Getting the library

Install via [Composer](https://getcomposer.org/):

```bash
composer require sk-id-solutions/smart-id-php-client
```

## Changelog

Changes introduced with new library versions are described in [CHANGELOG.md](CHANGELOG.md).

# How to use it with RP API v3

Import the relevant classes from the `Sk\SmartId` namespace:

```php
use Sk\SmartId\Api\SmartIdRestConnector;
use Sk\SmartId\Ssl\SslPinnedPublicKeyStore;
```

## Test accounts for testing

[Test accounts for testing](https://sk-eid.github.io/smart-id-documentation/test_accounts.html)

> **Note:** Smart-ID Basic level accounts (certificate level `ADVANCED`) are not supported in the demo environment.

## Setting up SmartIdClient

`SmartIdClient` is the main entry point for using Smart-ID services. It wires together the connector, session status poller, and request builders.

```php
use Sk\SmartId\SmartIdClient;
use Sk\SmartId\Ssl\SslPinnedPublicKeyStore;

// Demo environment
$client = new SmartIdClient(
    relyingPartyUUID: '00000000-0000-4000-8000-000000000000',
    relyingPartyName: 'DEMO',
    hostUrl: 'https://sid.demo.sk.ee/smart-id-rp/v3',
    sslPinnedKeys: SslPinnedPublicKeyStore::loadDemo(),
);

// Production environment
$sslKeys = SslPinnedPublicKeyStore::create()
    ->addPublicKeyHash('sha256//XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX=')
    ->addPublicKeyHash('sha256//YYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYY=');

$client = new SmartIdClient(
    relyingPartyUUID: 'your-relying-party-uuid',
    relyingPartyName: 'Your RP Name',
    hostUrl: 'https://rp-api.smart-id.com/v3',
    sslPinnedKeys: $sslKeys,
);

// Create authentication builders directly from the client
$deviceLinkBuilder = $client->createDeviceLinkAuthentication();
$notificationBuilder = $client->createNotificationAuthentication();

// Get the session status poller
$poller = $client->getSessionStatusPoller();

// Configure polling parameters (optional)
$client->setPollTimeoutMs(30000);
$client->setPollIntervalMs(1000);
```

You can also use the connector directly for lower-level control (see [Setting up the connector](#setting-up-the-connector)).

## Setting up the connector

[Configure to use with Smart-ID Demo environment](https://sk-eid.github.io/smart-id-documentation/environments.html#_demo)

```php
use Sk\SmartId\Api\SmartIdRestConnector;
use Sk\SmartId\Ssl\SslPinnedPublicKeyStore;

// Demo environment
$connector = new SmartIdRestConnector(
    'https://sid.demo.sk.ee/smart-id-rp/v3',
    SslPinnedPublicKeyStore::loadDemo(),
);

// Production environment
$sslKeys = SslPinnedPublicKeyStore::create()
    ->addPublicKeyHash('sha256//XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX=')
    ->addPublicKeyHash('sha256//YYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYY=');

$connector = new SmartIdRestConnector('https://rp-api.smart-id.com/v3', $sslKeys);
```

### Setting up HTTPS public key pinning

HTTPS public key pinning is used to prevent man-in-the-middle attacks against the Smart-ID API connection.
The SDK handles this automatically — Guzzle's default SSL verification is disabled and replaced with
[CURLOPT_PINNEDPUBLICKEY](https://curl.se/libcurl/c/CURLOPT_PINNEDPUBLICKEY.html) pinning against SK's server certificate public keys.

Live SSL certificates: https://sk-eid.github.io/smart-id-documentation/https_pinning.html#_rp_api_smart_id_com_certificates

Demo SSL certificates: https://sk-eid.github.io/smart-id-documentation/https_pinning.html#_sid_demo_sk_ee_certificates

#### Providing SSL public key hashes manually

For production, you must configure your own SPKI hashes. Get the current certificate hashes from
[SK's HTTPS pinning documentation](https://sk-eid.github.io/smart-id-documentation/https_pinning.html).

Extract the SPKI hash from a PEM certificate:

```bash
openssl x509 -inform PEM -in certificate.pem -noout -pubkey \
  | openssl rsa -pubin -outform der 2>/dev/null \
  | openssl dgst -sha256 -binary \
  | openssl enc -base64
```

Then provide the hashes:

```php
$sslKeys = SslPinnedPublicKeyStore::create()
    ->addPublicKeyHash('sha256//XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX=')
    ->addPublicKeyHash('sha256//YYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYY=');

$connector = new SmartIdRestConnector('https://rp-api.smart-id.com/v3', $sslKeys);
```

#### Loading hashes from an environment variable

When hashes are stored in a single environment variable (e.g. from `.env` or container config):

```bash
SMARTID_SSL_PINS="sha256//XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX=,sha256//YYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYY="
```

```php
$sslKeys = SslPinnedPublicKeyStore::fromString(getenv('SMARTID_SSL_PINS'));

$connector = new SmartIdRestConnector('https://rp-api.smart-id.com/v3', $sslKeys);
```

A custom separator can be provided as the second argument:

```php
// Semicolon-separated
$sslKeys = SslPinnedPublicKeyStore::fromString(getenv('SMARTID_SSL_PINS'), ';');
```

#### Loading hashes from an array (secret managers, config files)

When your secret manager or configuration returns an array of hash strings:

```php
$hashes = $secretManager->getSecret('smartid-ssl-pins'); // returns string[]

$sslKeys = SslPinnedPublicKeyStore::fromArray($hashes);

$connector = new SmartIdRestConnector('https://rp-api.smart-id.com/v3', $sslKeys);
```

All methods that accept hashes (`addPublicKeyHash()`, `fromString()`, `fromArray()`) validate
every hash immediately and throw `\InvalidArgumentException` if the format is invalid or the input is empty.

#### Loading hashes from a directory

You can load hashes from a directory of `.key` files (each containing one `sha256//...` hash):

```php
$sslKeys = SslPinnedPublicKeyStore::loadFromDirectory('/path/to/your/keys');
$connector = new SmartIdRestConnector('https://rp-api.smart-id.com/v3', $sslKeys);
```

#### Demo / testing

For development against `sid.demo.sk.ee`, the SDK bundles demo keys:

```php
$connector = new SmartIdRestConnector(
    'https://sid.demo.sk.ee/smart-id-rp/v3',
    SslPinnedPublicKeyStore::loadDemo(),
);
```

## Device link flows

Device link flows are the more secure way to ensure the user who started the authentication is in control of the device.
More info: https://sk-eid.github.io/smart-id-documentation/rp-api/device_link_flows.html

### Device link authentication session

#### Request parameters

- **relyingPartyUUID** — Required. UUID of the Relying Party.
- **relyingPartyName** — Required. Friendly name of the Relying Party, limited to 32 bytes in UTF-8 encoding.
- **certificateLevel** — Level of certificate requested. Possible values: `QUALIFIED`, `ADVANCED`. Defaults to `QUALIFIED`.
- **hashAlgorithm** — Hash algorithm for signatures. Supported: `SHA-256`, `SHA-384`, `SHA-512`, `SHA3-256`, `SHA3-384`, `SHA3-512`. Defaults to `SHA-512`.
- **interactions** — Required. Array of `DeviceLinkInteraction` objects defining the allowed interactions in order of preference.
- **callbackUrl** — Optional. HTTPS callback URL for Web2App same-device flows.
- **nonce** — Optional. Random string, up to 30 characters. Used to override idempotent behaviour (if the same request is made within a 15-second window, the same response is returned unless a nonce is provided).
- **capabilities** — Optional. Array of capability strings. Used only when agreed with Smart-ID provider.
- **requestProperties** — Optional. Set `shareMdClientIpAddress` to `true` to request the IP address of the user's device (see [Requesting IP address](#requesting-ip-address-of-users-device)).

#### Response parameters

- **sessionID** — String used to query the session status.
- **sessionToken** — Unique token linking this session between RP, RP-API, and the mobile app.
- **sessionSecret** — Base64-encoded secret key. Keep on backend only, never expose to client.
- **deviceLinkBase** — Base URI for forming device link or QR code URLs.

#### Using the request builder

```php
use Sk\SmartId\DeviceLink\DeviceLinkAuthenticationRequestBuilder;
use Sk\SmartId\Enum\CertificateLevel;
use Sk\SmartId\DeviceLink\DeviceLinkInteraction;

$builder = new DeviceLinkAuthenticationRequestBuilder(
    $connector,
    '00000000-0000-4000-8000-000000000000', // relyingPartyUUID
    'DEMO',                                  // relyingPartyName
);

// Initiate anonymous authentication (no user identifier needed)
$session = $builder
    ->withCertificateLevel(CertificateLevel::QUALIFIED)
    ->withAllowedInteractionsOrder([
        DeviceLinkInteraction::displayTextAndPin('Log in to example.com'),
    ])
    ->initiate();

// Session ID for polling
$sessionId = $session->getSessionId();

// Verification code to display (if using notification-style interaction)
$verificationCode = $session->getVerificationCode();

// Build QR code URL (see "Generating QR code or device link" section)
$qrCodeUrl = $session->buildQrCodeUrl();
```

The builder automatically generates the RP challenge and calculates the verification code.
If needed, you can provide your own RP challenge:

```php
use Sk\SmartId\Util\RpChallengeGenerator;

$rpChallenge = RpChallengeGenerator::generate();

$session = $builder
    ->withRpChallenge($rpChallenge)
    ->withAllowedInteractionsOrder([
        DeviceLinkInteraction::displayTextAndPin('Log in to example.com'),
    ])
    ->initiate();
```

#### Using request objects directly

For lower-level control, construct request objects directly:

```php
use Sk\SmartId\DeviceLink\DeviceLinkAuthenticationRequest;
use Sk\SmartId\Enum\CertificateLevel;
use Sk\SmartId\Enum\HashAlgorithm;
use Sk\SmartId\DeviceLink\DeviceLinkInteraction;
use Sk\SmartId\Util\RpChallengeGenerator;

// For security, generate a new challenge for each request
$rpChallenge = RpChallengeGenerator::generate();

$interactions = [DeviceLinkInteraction::displayTextAndPin('Log in to example.com')];

$request = new DeviceLinkAuthenticationRequest(
    relyingPartyUUID: '00000000-0000-4000-8000-000000000000',
    relyingPartyName: 'DEMO',
    rpChallenge: $rpChallenge,
    hashAlgorithm: HashAlgorithm::SHA512,
    allowedInteractionsOrder: $interactions,
    certificateLevel: CertificateLevel::QUALIFIED,
);

$response = $connector->initiateDeviceLinkAuthentication($request);

// Store these on the backend for later use
$sessionId = $response->getSessionID();
$sessionToken = $response->getSessionToken();
$sessionSecret = $response->getSessionSecret(); // Keep secret, do not expose to client
$deviceLinkBase = $response->getDeviceLinkBase();
```

### Generating QR code or device link

Documentation: https://sk-eid.github.io/smart-id-documentation/rp-api/device_link_flows.html

To use the Smart-ID **demo environment**, you must specify `SchemeName::DEMO` as the scheme name
(use `->withDemoEnvironment()` or `->withSchemeName(SchemeName::DEMO)`).
See: https://sk-eid.github.io/smart-id-documentation/environments.html#_demo

#### Device link parameters

- **deviceLinkBase** — Value from session-init response.
- **deviceLinkType** — `QR` or `WEB2APP`.
- **sessionToken** — Token from the session response.
- **elapsedSeconds** — Seconds since the session-init response was received. Required for QR codes.
- **lang** — User language. Default: `eng`.
- **schemeName** — Controls environment. Default: `SchemeName::PRODUCTION`. Use `SchemeName::DEMO` for demo.
- **callbackUrl** — Required for Web2App flows. Must be HTTPS.

#### Generating QR code URL

QR code URLs must be **refreshed every second** because the authCode changes based on elapsed time to prevent replay attacks.

```php
// Using the session object (simplest approach)
$session = $builder->initiate();

// QR code URL auto-calculates elapsed time from session creation
$qrCodeUrl = $session->buildQrCodeUrl();

// Or provide elapsed seconds explicitly
$qrCodeUrl = $session->buildQrCodeUrl(elapsedSeconds: 5);
```

Using the `DeviceLinkBuilder` for more control:

```php
$qrCodeUrl = $session->createDeviceLinkBuilder()
    ->withElapsedSeconds($elapsedSeconds)
    ->withDemoEnvironment() // for demo environment
    ->withLang('est')       // override language
    ->buildQrCodeUrl();
```

#### Generating Web2App URL

For mobile web browsers where the user can open the Smart-ID app directly:

```php
use Sk\SmartId\Util\CallbackUrlUtil;
use Sk\SmartId\Util\CallbackUrlValidator;

// Validate and create callback URL with a cryptographically random token
$callbackBase = 'https://your-app.com/callback';
CallbackUrlValidator::validateOrThrow($callbackBase, requireHttps: true);
$callbackResult = CallbackUrlUtil::createCallbackUrl($callbackBase);
$callbackUrl = $callbackResult['callbackUrl']; // e.g., https://your-app.com/callback?value=<random-token>
$callbackToken = $callbackResult['token'];        // Store to verify the callback later

// Callback URL must be set when initiating the session
$session = $builder
    ->withCallbackUrl($callbackUrl)
    ->withAllowedInteractionsOrder([
        DeviceLinkInteraction::displayTextAndPin('Log in'),
    ])
    ->initiate();

$web2AppUrl = $session->buildWeb2AppUrl();
```

#### Overriding default values

```php
$builder = $session->createDeviceLinkBuilder()
    ->withDemoEnvironment()             // override scheme for demo
    ->withLang('est')                  // override language
    ->withElapsedSeconds($elapsed);

$qrCodeUrl = $builder->buildQrCodeUrl();
```

### Examples of allowed device link interactions

An app can support different interaction types, and a Relying Party can specify preferred interactions with or without fallback options.
For device link flows, the available interaction types are limited to `displayTextAndPIN` and `confirmationMessage`.
`displayTextAndPIN` is used for short text with PIN-code input, while `confirmationMessage` is used for longer text with Confirm and Cancel buttons
and a second screen to enter the PIN-code.

**Example 1:** `confirmationMessage` with fallback to `displayTextAndPIN`

The RP's first choice is `confirmationMessage`; if not available, then fall back to `displayTextAndPIN`.

```php
$builder->withAllowedInteractionsOrder([
    DeviceLinkInteraction::confirmationMessage('Up to 200 characters of text here..'),
    DeviceLinkInteraction::displayTextAndPin('Up to 60 characters of text here..'),
]);
```

**Example 2:** `confirmationMessage` only (no fallback)

If the interaction is not supported by the app, the process will fail if no fallback is provided.

```php
$builder->withAllowedInteractionsOrder([
    DeviceLinkInteraction::confirmationMessage('Up to 200 characters of text here..'),
]);
```

## Notification-based flows

### Differences between notification-based and device link flows

- **Notification-based flow** — Push notification is sent directly to the user's Smart-ID app. Requires knowing the user's identity (document number or semantics identifier) beforehand. More vulnerable to phishing; recommended to use after user identity has been established via device link flow. No QR codes needed.
- **Device link flow** — Generates QR codes or deep links. Supports anonymous authentication where the user's identity is not required beforehand. QR code must be refreshed every second.

### Notification-based authentication session

#### Request parameters

- **relyingPartyUUID** — Required. UUID of the Relying Party.
- **relyingPartyName** — Required. Friendly name of the Relying Party.
- **documentNumber** or **semanticsIdentifier** — Required (one of). Identifies the user.
- **certificateLevel** — Optional. `QUALIFIED` or `ADVANCED`. Defaults to `QUALIFIED`.
- **hashAlgorithm** — Optional. Supported: `SHA-256`, `SHA-384`, `SHA-512`, `SHA3-256`, `SHA3-384`, `SHA3-512`. Defaults to `SHA-512`.
- **interactions** — Required. Array of `NotificationInteraction` objects defining the allowed interactions in order of preference.
- **nonce** — Optional. Random string, up to 30 characters. Used to override idempotent behaviour (if the same request is made within a 15-second window, the same response is returned unless a nonce is provided).
- **capabilities** — Optional. Array of capability strings.
- **requestProperties** — Optional. Set `shareMdClientIpAddress` to `true` to request the IP address of the user's device (see [Requesting IP address](#requesting-ip-address-of-users-device)).

#### Response parameters

- **sessionID** — String used to query the session status.

#### Initiating with semantics identifier

More info about Semantics Identifiers: [ETSI EN 319 412-1](https://www.etsi.org/deliver/etsi_en/319400_319499/31941201/01.01.00_30/en_31941201v010100v.pdf)

```php
use Sk\SmartId\Notification\NotificationAuthenticationRequestBuilder;
use Sk\SmartId\Notification\NotificationInteraction;
use Sk\SmartId\Enum\CertificateLevel;
use Sk\SmartId\Model\SemanticsIdentifier;

// Create semantics identifier:
// Type: PNO (personal number), PAS (passport), IDC (national identity card)
// Country: 2-letter ISO 3166-1 alpha-2 code
$semanticsIdentifier = SemanticsIdentifier::forPerson('EE', '30303039914');
// Or from a full string:
// $semanticsIdentifier = SemanticsIdentifier::fromString('PNOEE-30303039914');

$builder = new NotificationAuthenticationRequestBuilder(
    $connector,
    '00000000-0000-4000-8000-000000000000', // relyingPartyUUID
    'DEMO',                                  // relyingPartyName
);

$session = $builder
    ->withSemanticsIdentifier($semanticsIdentifier)
    ->withCertificateLevel(CertificateLevel::QUALIFIED)
    ->withAllowedInteractionsOrder([
        NotificationInteraction::confirmationMessageAndVerificationCodeChoice('Log in to example.com'),
        NotificationInteraction::displayTextAndPin('Log in to example.com'),
    ])
    ->initiate();

// Display verification code to the user
$verificationCode = $session->getVerificationCode();

// Use session ID to poll for status
$sessionId = $session->getSessionId();
```

Jump to [Querying session status](#querying-session-status) for an example of session status polling.

#### Initiating with document number

```php
$session = $builder
    ->withDocumentNumber('PNOLT-40504040001-MOCK-Q')
    ->withCertificateLevel(CertificateLevel::QUALIFIED)
    ->withAllowedInteractionsOrder([
        NotificationInteraction::displayTextAndPin('Log in to example.com'),
    ])
    ->initiate();

$verificationCode = $session->getVerificationCode();
$sessionId = $session->getSessionId();
```

### Examples of allowed notification-based interactions

Notification-based flows support additional interaction types compared to device link flows.
Available types are `displayTextAndPIN`, `confirmationMessage`, and `confirmationMessageAndVerificationCodeChoice`.

**Example 1:** `confirmationMessageAndVerificationCodeChoice` with fallback to `confirmationMessage` and `displayTextAndPIN`

The RP's first choice is `confirmationMessageAndVerificationCodeChoice`; the second choice is `confirmationMessage`; the third choice is `displayTextAndPIN`.

```php
$builder->withAllowedInteractionsOrder([
    NotificationInteraction::confirmationMessageAndVerificationCodeChoice('Up to 200 characters of text here...'),
    NotificationInteraction::confirmationMessage('Up to 200 characters of text here...'),
    NotificationInteraction::displayTextAndPin('Up to 60 characters of text here...'),
]);
```

**Example 2:** `confirmationMessageAndVerificationCodeChoice` only (no fallback)

Process will fail if interaction is not supported and there is no fallback.

```php
$builder->withAllowedInteractionsOrder([
    NotificationInteraction::confirmationMessageAndVerificationCodeChoice('Up to 200 characters of text here...'),
]);
```

## Querying session status

### Session status response

The session status response includes various fields depending on whether the session has completed or is still running:

- **state** — `RUNNING` or `COMPLETE`
- **result.endResult** — Outcome of the session (see [End result values](#end-result-values) below).
- **result.documentNumber** — Document number returned when `endResult` is `OK`. Can be used in further authentication requests to target the same device.
- **signatureProtocol** — `ACSP_V2` for authentication.
- **signature** — For `ACSP_V2`: contains `value`, `serverRandom`, `userChallenge`, `flowType`, `signatureAlgorithm`, `signatureAlgorithmParameters`.
- **cert** — Certificate info: `value` (Base64-encoded X.509 DER) and `certificateLevel` (`QUALIFIED` or `ADVANCED`).
- **interactionTypeUsed** — The interaction type that was actually used for the session (e.g., `displayTextAndPIN`, `confirmationMessage`).
- **ignoredProperties** — Array of property names from the request that were not recognized by the server.
- **deviceIpAddress** — IP address of the user's device, if `requestProperties.shareMdClientIpAddress` was set to `true` and the feature is enabled for your account.

#### End result values

The `result.endResult` field may contain the following values:

- **`OK`** — Session completed successfully.
- **`USER_REFUSED`** — User refused the session.
- **`USER_REFUSED_CERT_CHOICE`** — User has multiple accounts and pressed Cancel on device choice screen.
- **`USER_REFUSED_DISPLAYTEXTANDPIN`** — User pressed Cancel on the `displayTextAndPIN` interaction screen.
- **`USER_REFUSED_VC_CHOICE`** — User pressed Cancel on the verification code choice screen.
- **`USER_REFUSED_CONFIRMATIONMESSAGE`** — User pressed Cancel on the `confirmationMessage` screen.
- **`USER_REFUSED_CONFIRMATIONMESSAGE_WITH_VC_CHOICE`** — User pressed Cancel on the `confirmationMessageAndVerificationCodeChoice` screen.
- **`USER_REFUSED_INTERACTION`** — User pressed Cancel on the interaction screen. `result.details` contains info about which interaction was canceled.
- **`TIMEOUT`** — User did not respond in time.
- **`DOCUMENT_UNUSABLE`** — Session could not be completed due to an issue with the document.
- **`WRONG_VC`** — User selected the wrong verification code.
- **`REQUIRED_INTERACTION_NOT_SUPPORTED_BY_APP`** — The requested interaction is not supported by the user's app.
- **`PROTOCOL_FAILURE`** — An error occurred in the signing protocol.
- **`EXPECTED_LINKED_SESSION`** — Server expected a device link session but received a notification-based session (or vice versa).
- **`SERVER_ERROR`** — Technical error occurred at the server side and the process was terminated.
- **`ACCOUNT_UNUSABLE`** — The user's Smart-ID account is unusable for this operation.

#### End result to exception mapping

When using `SessionStatusPoller`, non-OK end results are automatically converted to typed exceptions:

| End result | Exception |
|---|---|
| `USER_REFUSED_INTERACTION` | `UserRefusedInteractionException` |
| `USER_REFUSED`, `USER_REFUSED_CERT_CHOICE`, `USER_REFUSED_DISPLAYTEXTANDPIN`, `USER_REFUSED_VC_CHOICE`, `USER_REFUSED_CONFIRMATIONMESSAGE`, `USER_REFUSED_CONFIRMATIONMESSAGE_WITH_VC_CHOICE` | `UserRefusedException` |
| `TIMEOUT` | `SessionTimeoutException` |
| `DOCUMENT_UNUSABLE` | `DocumentUnusableException` |
| `WRONG_VC` | `WrongVerificationCodeException` |
| `REQUIRED_INTERACTION_NOT_SUPPORTED_BY_APP` | `RequiredInteractionNotSupportedException` |
| `PROTOCOL_FAILURE` | `ProtocolFailureException` |
| `SERVER_ERROR` | `ServerErrorException` |
| `ACCOUNT_UNUSABLE`, `EXPECTED_LINKED_SESSION`, other | `SmartIdException` |

> **Note:** `UserRefusedInteractionException` extends `UserRefusedException`. The poller checks for `USER_REFUSED_INTERACTION` first, so catching `UserRefusedException` after `UserRefusedInteractionException` handles all other user refusal variants.

### Polling for final session status

Using `SessionStatusPoller` to poll until the session completes:

```php
use Sk\SmartId\Session\SessionStatusPoller;

$poller = new SessionStatusPoller($connector);

// Poll until session completes (blocks with long polling)
$sessionStatus = $poller->pollUntilComplete($sessionId);

if ($sessionStatus->isComplete()) {
    $endResult = $sessionStatus->getResult()->getEndResult();
    // 'OK' means authentication succeeded
}
```

The poller automatically throws typed exceptions for error results (see [Exception handling](#exception-handling)).

You can configure polling parameters:

```php
$poller = new SessionStatusPoller($connector);
$poller->setPollTimeoutMs(30000);  // Server-side long poll timeout (default: 30s)
$poller->setPollIntervalMs(1000);  // Interval between poll attempts (default: 1s)

// Limit the number of poll attempts
$sessionStatus = $poller->pollUntilComplete($sessionId, maxAttempts: 60);
```

### Single status query

For device link flows where you need to generate a fresh QR code every second, use single status queries:

```php
$poller = new SessionStatusPoller($connector);

$sessionStatus = $poller->poll($sessionId);

if ($sessionStatus->isRunning()) {
    // Session still in progress — refresh QR code and poll again
} elseif ($sessionStatus->isComplete()) {
    // Proceed to validation
}
```

Or query the connector directly:

```php
$sessionStatus = $connector->getSessionStatus($sessionId, timeoutMs: 1000);
```

## Validating authentication response

It is critical to validate the authentication response to ensure the signature and certificate are trustworthy.

### Setting up trusted CA certificates

```php
use Sk\SmartId\Validation\AuthenticationResponseValidator;
use Sk\SmartId\Validation\TrustedCACertificateStore;

$validator = new AuthenticationResponseValidator();

// Option 1: Load production certificates bundled with the SDK
TrustedCACertificateStore::loadFromDefaults()->configureValidator($validator);

// Option 2: Load test certificates for demo environment
TrustedCACertificateStore::loadTestCertificates()->configureValidator($validator);

// Option 3: Load from a custom directory
TrustedCACertificateStore::loadFromDirectory('/path/to/certs')->configureValidator($validator);

// Option 4: Add certificates manually
$store = TrustedCACertificateStore::create()
    ->addCertificate($pemEncodedCert)
    ->addCertificateFromFile('/path/to/cert.pem.crt');
$store->configureValidator($validator);
```

### Validating device link authentication

```php
use Sk\SmartId\Validation\AuthenticationResponseValidator;
use Sk\SmartId\Validation\TrustedCACertificateStore;
use Sk\SmartId\Enum\CertificateLevel;
use Sk\SmartId\Util\AuthCodeCalculator;

// Set up validator
$validator = new AuthenticationResponseValidator();
TrustedCACertificateStore::loadTestCertificates()->configureValidator($validator);

// The interactions Base64 value must match what was sent in the original request
$interactions = [DeviceLinkInteraction::displayTextAndPin('Log in to example.com')];
$interactionsBase64 = base64_encode(json_encode(
    array_map(fn (DeviceLinkInteraction $i) => $i->toArray(), $interactions),
    JSON_THROW_ON_ERROR,
));

// Validate and extract identity
$identity = $validator->validate(
    $sessionStatus,
    $rpChallenge,                             // Base64-encoded RP challenge from the original request
    'DEMO',                                   // Relying Party name
    $interactionsBase64,                      // Base64-encoded interactions JSON
    requiredCertificateLevel: CertificateLevel::QUALIFIED,
    schemeName: SchemeName::DEMO, // Use SchemeName::PRODUCTION for live
);
```

The validator performs the following checks:
1. Session state is `COMPLETE` and result is `OK`
2. Signature protocol is `ACSP_V2`
3. Certificate level meets the requested level (if specified)
4. Certificate is signed by a trusted CA and within its validity period
5. Certificate basic constraints (not a CA certificate)
6. Certificate revocation via OCSP (if configured)
7. Certificate policies contain Smart-ID scheme OIDs
8. Certificate key usage includes `digitalSignature`
9. Certificate Extended Key Usage includes Smart-ID authentication or `clientAuth`
10. `signatureAlgorithmParameters` validation (hashAlgorithm, saltLength, maskGenAlgorithm, trailerField)
11. RSA-PSS signature verification against the ACSP_V2 payload

### Validating notification-based authentication

Validation is the same as device link. The only difference is how the session was initiated — the validation step uses the same `AuthenticationResponseValidator::validate()` method.

```php
$identity = $validator->validate(
    $sessionStatus,
    $session->getRpChallenge(),
    'DEMO',
    $interactionsBase64,
    requiredCertificateLevel: CertificateLevel::QUALIFIED,
    schemeName: SchemeName::DEMO,
);
```

### OCSP certificate revocation checking

Per Smart-ID documentation, OCSP revocation checking is a required validation step for production.

```php
use Sk\SmartId\Validation\OcspCertificateRevocationChecker;

// Enable OCSP checking (production)
TrustedCACertificateStore::loadFromDefaults()->configureValidatorWithOcsp($validator);

// Or with a custom OCSP checker
$ocspChecker = new OcspCertificateRevocationChecker(timeoutSeconds: 10);
TrustedCACertificateStore::loadFromDefaults()->configureValidatorWithOcsp($validator, $ocspChecker);
```

> **Note:** The demo OCSP responder at `aia.demo.sk.ee` intentionally reports test certificates as revoked.
> Use `configureValidator()` (without OCSP) for the demo environment and `configureValidatorWithOcsp()` for production.

### Web2App flow validation

When using Web2App or App2App flows, the Smart-ID app redirects the user back to the Relying Party via the callback URL. The received callback URL will contain additional query parameters that must be validated.

Example callback URL for authentication:
```
https://rp.example.com/callback?value=RrKjjT4aggzu27YBddX1bQ&sessionSecretDigest=U4CKK13H1XFiyBofev9asqrzIrY5_Gszi_nL_zDKkBc&userChallengeVerifier=XtPfaGa8JnGtYrJjboooUf0KfY9sMEHrWFpSQrsUv9c
```

Validation steps:

1. Verify that the `value` query parameter matches the random value you included when creating the callback URL.
2. Verify `sessionSecretDigest` matches `Base64URL(SHA-256(Base64Decode(sessionSecret)))` where `sessionSecret` is from the session init response.
3. For authentication flows, verify `userChallengeVerifier` — its SHA-256 hash (Base64URL-encoded) must match `signature.userChallenge` from the session status response.

```php
// 1. Verify session secret from callback URL
$validator->verifySessionSecret(
    $sessionSecret,        // from the original session response
    $sessionSecretDigest,  // from the callback URL query parameter
);

// 2. Verify user challenge from callback URL
$validator->verifyUserChallenge(
    $userChallengeVerifier,      // from the callback URL query parameter
    $userChallengeFromResponse,  // from session status response signature.userChallenge
);

// 3. Then proceed with standard validation
$identity = $validator->validate($sessionStatus, ...);
```

### Validating callback URL with CallbackUrlUtil

The `CallbackUrlUtil` helper simplifies creating and validating callback URLs for Web2App/App2App flows.

#### Creating a callback URL with a random token

```php
use Sk\SmartId\Util\CallbackUrlUtil;

// Generate a callback URL with a cryptographically random token
$result = CallbackUrlUtil::createCallbackUrl('https://your-app.com/callback');
$callbackUrl = $result['callbackUrl']; // e.g., https://your-app.com/callback?value=abc123...
$token = $result['token'];             // Store this to verify the callback later

// Use the callback URL when initiating the session
$session = $builder
    ->withCallbackUrl($callbackUrl)
    ->withAllowedInteractionsOrder([...])
    ->initiate();
```

#### Validating session secret digest from callback

When the Smart-ID app redirects back to your callback URL, validate the `sessionSecretDigest` query parameter:

```php
use Sk\SmartId\Util\CallbackUrlUtil;

// Throws ValidationException if digest does not match
CallbackUrlUtil::validateSessionSecretDigest(
    $sessionSecretDigest,  // from the callback URL query parameter
    $sessionSecret,        // from the original session init response
);
```

## Extracting user identity

After successful validation, the `AuthenticationIdentity` object provides:

```php
$identity->getGivenName();    // e.g., 'QUALIFIED OK1'
$identity->getSurname();      // e.g., 'TESTNUMBER'
$identity->getFullName();     // e.g., 'QUALIFIED OK1 TESTNUMBER'
$identity->getIdentityCode(); // e.g., '30303039914'
$identity->getCountry();      // e.g., 'EE'

// Additional methods for Baltic states (EE, LV, LT):
$identity->getDateOfBirth();  // DateTimeImmutable or null
$identity->getGender();       // 'M', 'F', or null
$identity->getAge();          // int or null
```

The document number can be retrieved from the session result for future requests:

```php
$documentNumber = $sessionStatus->getResult()->getDocumentNumber();
```

## Additional request properties

### Requesting IP address of user's device

For the IP address to be returned, the service provider (SK) must enable this option for your account.
More info: [https://sk-eid.github.io/smart-id-documentation/rp-api/3.0.3/request_properties.html](https://sk-eid.github.io/smart-id-documentation/rp-api/3.0.3/request_properties.html)

```php
// Device link authentication with IP address sharing
$session = $builder
    ->withCertificateLevel(CertificateLevel::QUALIFIED)
    ->withAllowedInteractionsOrder([
        DeviceLinkInteraction::displayTextAndPin('Log in to example.com'),
    ])
    ->withShareMdClientIpAddress()
    ->initiate();

// After session completes, retrieve the device IP address
$sessionStatus = $poller->pollUntilComplete($session->getSessionId());
$deviceIpAddress = $sessionStatus->getDeviceIpAddress(); // IP address or null
```

The same `withShareMdClientIpAddress()` method is available on both `DeviceLinkAuthenticationRequestBuilder` and `NotificationAuthenticationRequestBuilder`.

## Exception handling

The library provides specific exceptions for different error scenarios. All exceptions extend `SmartIdException`.

### Permanent exceptions

These indicate client-side configuration or input errors.

- **`InvalidParametersException`** — Invalid request parameters (HTTP 400).
- **`UnauthorizedException`** — Invalid RP credentials (HTTP 401) or user not found (HTTP 403).

### User action exceptions

These cover scenarios where user actions or inactions lead to session termination.

- **`UserRefusedException`** — User refused the operation.
- **`UserRefusedInteractionException`** — User pressed Cancel on a specific interaction screen. Extends `UserRefusedException`. Use `getInteraction()` to see which interaction was canceled (from `result.details`).
- **`SessionTimeoutException`** — User did not respond within the allowed timeframe.
- **`WrongVerificationCodeException`** — User selected the wrong verification code.
- **`RequiredInteractionNotSupportedException`** — The required interaction type is not supported by the user's app.

### User account exceptions

- **`UserAccountException`** — Problems with the user's Smart-ID account.
    - `isNoSuitableAccount()` — No suitable account found (HTTP 471).
    - `isPersonShouldViewApp()` — User should view Smart-ID app or self-service portal (HTTP 472).
    - `isClientTooOld()` — Client-side API too old (HTTP 480).
- **`DocumentUnusableException`** — The requested document cannot be used.

### Protocol and server exceptions

- **`ProtocolFailureException`** — An error occurred in the signing protocol.
- **`ServerErrorException`** — Technical error occurred at the Smart-ID server side.

### Validation exceptions

- **`ValidationException`** — Thrown during response validation: certificate trust, signature mismatch, OCSP revocation, etc.

### Technical exceptions

- **`TechnicalErrorException`** — Thrown for technical errors during processing (e.g., malformed responses, unexpected data formats).

### Server-side exceptions

- **`SmartIdException`** — Smart-ID service temporarily unavailable (HTTP 5xx) or other unclassified errors.
- **`SessionNotFoundException`** — Session not found (HTTP 404).

### Example of handling exceptions

```php
use Sk\SmartId\Exception\SmartIdException;
use Sk\SmartId\Exception\UserRefusedException;
use Sk\SmartId\Exception\UserRefusedInteractionException;
use Sk\SmartId\Exception\SessionTimeoutException;
use Sk\SmartId\Exception\WrongVerificationCodeException;
use Sk\SmartId\Exception\UserAccountException;
use Sk\SmartId\Exception\DocumentUnusableException;
use Sk\SmartId\Exception\RequiredInteractionNotSupportedException;
use Sk\SmartId\Exception\ProtocolFailureException;
use Sk\SmartId\Exception\ServerErrorException;
use Sk\SmartId\Exception\ValidationException;

try {
    $sessionStatus = $poller->pollUntilComplete($sessionId);
    $identity = $validator->validate($sessionStatus, ...);
} catch (SessionTimeoutException $e) {
    // User did not respond in time
} catch (UserRefusedInteractionException $e) {
    // User refused a specific interaction — check which one
    $interaction = $e->getInteraction(); // e.g., 'displayTextAndPIN'
} catch (UserRefusedException $e) {
    // User refused the operation (covers USER_REFUSED and all USER_REFUSED_* variants)
} catch (WrongVerificationCodeException $e) {
    // User selected wrong verification code
} catch (DocumentUnusableException $e) {
    // Document is unusable for this operation
} catch (RequiredInteractionNotSupportedException $e) {
    // Required interaction not supported by user's app
} catch (UserAccountException $e) {
    if ($e->isNoSuitableAccount()) {
        // No suitable Smart-ID account
    } elseif ($e->isPersonShouldViewApp()) {
        // User should check Smart-ID app
    } elseif ($e->isClientTooOld()) {
        // Client-side API too old
    }
} catch (ProtocolFailureException $e) {
    // Protocol failure — retry may help
} catch (ServerErrorException $e) {
    // Smart-ID server error — retry later
} catch (ValidationException $e) {
    // Authentication response validation failed — do not trust!
} catch (SmartIdException $e) {
    // General Smart-ID error (includes ACCOUNT_UNUSABLE, EXPECTED_LINKED_SESSION, HTTP 5xx)
}
```