# Smart-ID PHP SDK Examples

Plain PHP examples demonstrating Smart-ID authentication flows.

## Prerequisites

- PHP 8.4+
- `ext-curl`, `ext-json` and `ext-openssl` extensions enabled
- Composer

## Setup

```bash
cd examples/plain-php
composer install
```

This installs the SDK from the parent repository (via path) and the `chillerlan/php-qrcode` library used for QR code rendering.

## Running

Start PHP's built-in development server from the `examples/plain-php` directory:

```bash
cd examples/plain-php
php -S localhost:8080
```

Then open in your browser:

- **QR Code flow** — [http://localhost:8080/index.php](http://localhost:8080/index.php)
- **Notification flow** — [http://localhost:8080/notification.php](http://localhost:8080/notification.php)
- **Web2App flow** — [http://localhost:8080/web2app.php](http://localhost:8080/web2app.php) (requires HTTPS, see below)

## Examples

### QR Code Authentication (`index.php`)

Device link flow for desktop browsers. The page displays a QR code that the user scans with their phone camera to open the Smart-ID app. The QR code refreshes every second (contains a time-based `authCode`).

No user identity is required upfront — this supports anonymous authentication.

### Notification Authentication (`notification.php`)

Push notification flow. The user enters their country and national identity number, then receives a push notification on their Smart-ID app. A 4-digit verification code is displayed on screen that the user must match in the app.

### Web2App Authentication (`web2app.php`)

Deep link flow for mobile web browsers. The user taps a button to open the Smart-ID app directly on the same device. After authenticating, the app redirects back to the browser via a callback URL.

**Requires a public HTTPS URL** because:
- The Smart-ID API requires `initialCallbackUrl` to be HTTPS
- The Smart-ID app redirects to this URL after authentication

#### Running with ngrok

1. Start the PHP server:
   ```bash
   php -S localhost:8080
   ```

2. In a separate terminal, start ngrok:
   ```bash
   ngrok http 8080
   ```

3. Copy the ngrok HTTPS URL (e.g. `https://xxxx-xxxx.ngrok-free.app`) and update the `$publicBaseUrl` variable in `web2app.php`.

4. Open the ngrok URL on your **mobile phone** browser.

The callback flow validates:
- `sessionSecretDigest` — proves the redirect came from Smart-ID (not spoofed)
- `userChallengeVerifier` — proves the user actually completed authentication
- Full ACSP_V2 signature verification and certificate trust chain

## Test Accounts

These examples are configured for the **Smart-ID demo environment** (`sid.demo.sk.ee`).

Use the following test identities for the notification flow (Identity Number tab):

| Country | Identity Number | Name |
|---------|----------------|------|
| Estonia | 40504040001 | OK, TESTNUMBER |
| Belgium | 05040400032 | OK, TESTNUMBER |

These accounts are issued under **TEST of SK ID Solutions EID-Q 2024E** and pass full validation including Certificate Policy OID checks.

For the Document Number tab, use MOCK accounts like `PNOLT-40504040001-MOCK-Q`.

More test accounts: https://sk-eid.github.io/smart-id-documentation/test_accounts.html

Environment details: https://sk-eid.github.io/smart-id-documentation/environments.html

## Demo Environment Notes

- For production, use `TrustedCACertificateStore::loadFromDefaults()` instead of `loadTestCertificates()`.
- Replace the demo relying party UUID and name with your own production credentials.
- Replace `SslPinnedPublicKeyStore::loadDemo()` with your production SSL pin configuration.
- Replace `SchemeName::DEMO` with `SchemeName::PRODUCTION`.

## File Structure

```
examples/plain-php/
├── css/
│   ├── style.css          # Shared styles
│   ├── callback.css       # Web2App callback verification page styles (dark theme)
│   ├── index.css           # QR code page styles
│   ├── notification.css    # Notification page styles
│   └── web2app.css         # Web2App page styles
├── js/
│   ├── index.js            # QR code page logic
│   ├── notification.js     # Notification page logic
│   └── web2app.js          # Web2App page logic
├── index.php               # QR code authentication example
├── notification.php        # Notification authentication example
├── web2app.php             # Web2App deep link authentication example
├── composer.json
└── README.md
```
