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

/**
 * Smart-ID Web2App Authentication Example
 *
 * This example demonstrates how to implement deep link-based authentication
 * for MOBILE WEB BROWSERS. When the user clicks the authentication button,
 * the Smart-ID app opens directly on their phone.
 *
 * Use Case:
 * - User visits your website on their mobile phone browser
 * - User clicks "Log in with Smart-ID" button
 * - Smart-ID app opens automatically
 * - User authenticates in the app
 * - User returns to the browser (manually or via callback)
 *
 * Key Difference from QR Code:
 * - No QR code needed (user is already on their phone)
 * - No elapsed seconds tracking required
 * - Single click to open Smart-ID app
 *
 * Flow:
 * 1. User loads the page -> authentication session is initiated
 * 2. Deep link button is displayed
 * 3. User taps the button -> Smart-ID app opens
 * 4. User confirms authentication in the app
 * 5. Page polls for completion and shows success message
 */

require_once __DIR__ . '/vendor/autoload.php';

use Sk\SmartId\Api\SmartIdRestConnector;
use Sk\SmartId\Ssl\SslPinnedPublicKeyStore;
use Sk\SmartId\DeviceLink\DeviceLinkAuthenticationRequest;
use Sk\SmartId\DeviceLink\DeviceLinkAuthenticationSession;
use Sk\SmartId\Enum\CertificateLevel;
use Sk\SmartId\Enum\HashAlgorithm;
use Sk\SmartId\Model\Interaction;
use Sk\SmartId\Util\AuthCodeCalculator;
use Sk\SmartId\Util\CallbackUrlValidator;
use Sk\SmartId\Util\RpChallengeGenerator;
use Sk\SmartId\Util\VerificationCodeCalculator;
use Sk\SmartId\Validation\AuthenticationResponseValidator;
use Sk\SmartId\Validation\TrustedCACertificateStore;

session_start();

// ============================================================================
// CONFIGURATION
// ============================================================================

$baseUrl = 'https://sid.demo.sk.ee/smart-id-rp/v3';
$relyingPartyUUID = '00000000-0000-4000-8000-000000000000';
$relyingPartyName = 'DEMO';

// Public HTTPS URL for Web2App callback (required by Smart-ID API)
// Use ngrok or similar tunnel for local development: ngrok http 8080
$publicBaseUrl = 'https://3519-2001-7d0-89b7-e80-248e-5ba0-afc2-af4e.ngrok-free.app';

$connector = new SmartIdRestConnector(
    $baseUrl,
    SslPinnedPublicKeyStore::loadDemo(),
);

// ============================================================================
// AJAX ENDPOINTS
// ============================================================================
if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    // -------------------------------------------------------------------------
    // ACTION: init - Start a new authentication session
    // -------------------------------------------------------------------------
    if ($_GET['action'] === 'init') {
        $rpChallenge = RpChallengeGenerator::generate();

        // For Web2App, the callback URL must be sent to API during session init!
        // This is required so the Smart-ID backend can validate the authCode
        $callbackUrl = $publicBaseUrl . '/web2app.php?action=callback';

        // Validate callback URL format — Smart-ID API requires HTTPS
        CallbackUrlValidator::validateOrThrow($callbackUrl, requireHttps: true);

        $interactions = [Interaction::displayTextAndPin('Test login')];
        $interactionsBase64 = base64_encode(json_encode(
            array_map(fn (Interaction $i) => $i->toArray(), $interactions),
            JSON_THROW_ON_ERROR,
        ));

        $request = new DeviceLinkAuthenticationRequest(
            relyingPartyUUID: $relyingPartyUUID,
            relyingPartyName: $relyingPartyName,
            rpChallenge: $rpChallenge,
            hashAlgorithm: HashAlgorithm::SHA512,
            allowedInteractionsOrder: $interactions,
            certificateLevel: CertificateLevel::QUALIFIED,
            initialCallbackUrl: $callbackUrl,  // Required for Web2App!
        );

        $response = $connector->initiateDeviceLinkAuthentication($request);
        $verificationCode = VerificationCodeCalculator::calculateFromRpChallenge($rpChallenge);

        $_SESSION['auth'] = [
            'sessionId' => $response->getSessionID(),
            'sessionToken' => $response->getSessionToken(),
            'sessionSecret' => $response->getSessionSecret(),
            'deviceLinkBase' => $response->getDeviceLinkBase(),
            'rpChallenge' => $rpChallenge,
            'rpName' => $relyingPartyName,
            'interactionsBase64' => $interactionsBase64,
            'verificationCode' => $verificationCode,
            'createdAt' => time(),
            'callbackUrl' => $callbackUrl,  // Store for link generation
        ];

        echo json_encode([
            'success' => true,
            'verificationCode' => $verificationCode,
        ]);
        exit;
    }

    // -------------------------------------------------------------------------
    // ACTION: link - Get the Web2App deep link URL
    // Unlike QR codes, Web2App links don't need elapsed seconds tracking
    // -------------------------------------------------------------------------
    if ($_GET['action'] === 'link') {
        if (!isset($_SESSION['auth'])) {
            echo json_encode(['error' => 'No session']);
            exit;
        }

        $auth = $_SESSION['auth'];

        $response = new \Sk\SmartId\DeviceLink\DeviceLinkAuthenticationResponse(
            $auth['sessionId'],
            $auth['sessionToken'],
            $auth['sessionSecret'],
            $auth['deviceLinkBase'],
        );

        $session = new DeviceLinkAuthenticationSession(
            $response,
            $auth['rpChallenge'],
            $auth['rpName'],
            [Interaction::displayTextAndPin('Test login')],
            $auth['verificationCode'],
        );

        // Build Web2App URL - must use the SAME callback URL sent to API!
        $web2appUrl = $session->createDeviceLinkBuilder()
            ->withDemoEnvironment()
            ->withCallbackUrl($auth['callbackUrl'])
            ->buildWeb2AppUrl();

        echo json_encode([
            'url' => $web2appUrl,
            'verificationCode' => $auth['verificationCode'],
        ]);
        exit;
    }

    // -------------------------------------------------------------------------
    // ACTION: callback - Handle redirect from Smart-ID app after authentication
    // The app redirects here with sessionSecretDigest and userChallengeVerifier
    // Store them in session for use during status polling validation
    // -------------------------------------------------------------------------
    if ($_GET['action'] === 'callback') {
        // =========================================================
        // Per Smart-ID docs, validate callback query parameters:
        // 1. `value` must match sessionToken from init response
        // 2. `sessionSecretDigest` must be present (validated later)
        // 3. `userChallengeVerifier` must be present for auth flows
        // =========================================================
        if (!isset($_SESSION['auth'])) {
            header('Content-Type: text/html; charset=utf-8');
            http_response_code(403);
            echo '<h1>No active session</h1>';
            exit;
        }

        // Step 1: Verify URL token matches sessionToken
        $urlToken = $_GET['value'] ?? '';
        $expectedToken = $_SESSION['auth']['sessionToken'] ?? '';
        if (!hash_equals($expectedToken, $urlToken)) {
            header('Content-Type: text/html; charset=utf-8');
            http_response_code(403);
            echo '<h1>Invalid callback: URL token mismatch</h1>';
            exit;
        }

        // Store callback parameters in session for validation during status polling
        if (isset($_GET['sessionSecretDigest'])) {
            $_SESSION['auth']['callbackSessionSecretDigest'] = $_GET['sessionSecretDigest'];
        }
        if (isset($_GET['userChallengeVerifier'])) {
            $_SESSION['auth']['callbackUserChallengeVerifier'] = $_GET['userChallengeVerifier'];
        }

        // Override JSON content type — render HTML page that polls for status
        header('Content-Type: text/html; charset=utf-8');
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Verifying Authentication...</title>
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                       background: #0F172A; color: #E2E8F0; padding: 20px; text-align: center; }
                .card { background: #1E293B; border-radius: 16px; padding: 32px; max-width: 400px; margin: 40px auto; }
                h1 { margin: 16px 0 8px; font-size: 22px; }
                .spinner { width: 24px; height: 24px; border: 3px solid #334155; border-top-color: #22C55E;
                           border-radius: 50%; animation: spin 1s linear infinite; display: inline-block; }
                @keyframes spin { to { transform: rotate(360deg); } }
                .success-icon { color: #22C55E; font-size: 48px; }
                .error-icon { color: #EF4444; font-size: 48px; }
                .user-info { background: #334155; border-radius: 8px; padding: 16px; margin: 16px 0;
                             text-align: left; font-size: 14px; }
                .user-info p { margin: 8px 0; }
                .check-item { background: #334155; border-radius: 8px; padding: 10px 14px; margin: 6px 0;
                              text-align: left; font-size: 13px; display: flex; align-items: center; gap: 8px; }
                .check-ok { color: #22C55E; }
                .check-fail { color: #EF4444; }
                .hidden { display: none; }
            </style>
        </head>
        <body>
            <div class="card">
                <div id="loading">
                    <div class="spinner"></div>
                    <h1>Verifying authentication...</h1>
                    <p style="color: #94A3B8; font-size: 13px;">Polling session status and validating response</p>
                </div>
                <div id="result" class="hidden"></div>
            </div>
            <script>
                async function pollStatus() {
                    try {
                        const res = await fetch('?action=status');
                        const data = await res.json();

                        if (data.state !== 'COMPLETE') {
                            setTimeout(pollStatus, 1500);
                            return;
                        }

                        const resultEl = document.getElementById('result');
                        document.getElementById('loading').classList.add('hidden');
                        resultEl.classList.remove('hidden');

                        if (data.endResult === 'OK' && data.user) {
                            let checksHtml = '';
                            if (data.checks) {
                                checksHtml = data.checks.map(c =>
                                    `<div class="check-item">
                                        <span class="${c.ok ? 'check-ok' : 'check-fail'}">${c.ok ? '✓' : '✗'}</span>
                                        <span>${c.label}</span>
                                    </div>`
                                ).join('');
                            }
                            resultEl.innerHTML = `
                                <div class="success-icon">✓</div>
                                <h1>Authentication Verified!</h1>
                                <p style="color: #94A3B8; font-size: 13px; margin-bottom: 12px;">
                                    All production validation checks passed
                                </p>
                                ${checksHtml}
                                <div class="user-info">
                                    <p><strong>Name:</strong> ${data.user.fullName}</p>
                                    <p><strong>Identity Code:</strong> ${data.user.identityCode}</p>
                                    <p><strong>Country:</strong> ${data.user.country}</p>
                                    ${data.user.dateOfBirth ? `<p><strong>Date of Birth:</strong> ${data.user.dateOfBirth}</p>` : ''}
                                </div>`;
                        } else {
                            resultEl.innerHTML = `
                                <div class="error-icon">✗</div>
                                <h1>Authentication Failed</h1>
                                <p style="color: #EF4444; font-size: 14px;">
                                    ${data.error || data.endResult || 'Unknown error'}
                                </p>`;
                        }
                    } catch (e) {
                        setTimeout(pollStatus, 2000);
                    }
                }
                pollStatus();
            </script>
        </body>
        </html>
        <?php
        exit;
    }

    // -------------------------------------------------------------------------
    // ACTION: status - Poll for authentication session status
    // Performs full production-like validation when session is complete
    // AND callback parameters have been received
    // -------------------------------------------------------------------------
    if ($_GET['action'] === 'status') {
        if (!isset($_SESSION['auth'])) {
            echo json_encode(['error' => 'No session']);
            exit;
        }

        $status = $connector->getSessionStatus($_SESSION['auth']['sessionId'], timeoutMs: 1000);

        $response = [
            'state' => $status->getState(),
        ];

        if ($status->isComplete() && $status->getResult() !== null) {
            $response['endResult'] = $status->getResult()->getEndResult();

            if ($status->getResult()->isOk()) {
                // =========================================================
                // In Web2App flow, we MUST wait for the callback URL redirect
                // before validating. The callback delivers sessionSecretDigest
                // and userChallengeVerifier which prove the redirect actually
                // came from Smart-ID and the user completed authentication.
                // Without these, authentication should NOT be considered valid.
                // =========================================================
                $hasCallbackParams = isset(
                    $_SESSION['auth']['callbackSessionSecretDigest'],
                    $_SESSION['auth']['callbackUserChallengeVerifier'],
                );

                if (!$hasCallbackParams) {
                    // Session is complete but callback hasn't been received yet.
                    // Tell the frontend to keep waiting.
                    $response['state'] = 'WAITING_FOR_CALLBACK';
                    $response['endResult'] = null;
                    echo json_encode($response);
                    exit;
                }

                try {
                    $checks = [];

                    // =========================================================
                    // STEP 1: Verify sessionSecretDigest from callback URL
                    // Proves the callback came from Smart-ID (not spoofed)
                    // =========================================================
                    $validator = new AuthenticationResponseValidator();
                    $validator->verifySessionSecret(
                        $_SESSION['auth']['sessionSecret'],
                        $_SESSION['auth']['callbackSessionSecretDigest'],
                    );
                    $checks[] = ['label' => 'Session secret digest', 'ok' => true];

                    // =========================================================
                    // STEP 2: Verify userChallengeVerifier from callback URL
                    // Proves the user actually completed authentication
                    // =========================================================
                    if ($status->getSignature() !== null) {
                        $validator->verifyUserChallenge(
                            $_SESSION['auth']['callbackUserChallengeVerifier'],
                            $status->getSignature()->getUserChallenge(),
                        );
                        $checks[] = ['label' => 'User challenge verifier', 'ok' => true];
                    }

                    // =========================================================
                    // STEP 3: Full response validation
                    // Certificate trust, basic constraints, policies, purpose,
                    // ACSP_V2 signature verification — everything
                    // =========================================================
                    $validator = new AuthenticationResponseValidator();
                    TrustedCACertificateStore::loadTestCertificates()->configureValidator($validator);

                    $identity = $validator->validate(
                        $status,
                        $_SESSION['auth']['rpChallenge'],
                        $_SESSION['auth']['rpName'],
                        $_SESSION['auth']['interactionsBase64'],
                        $_SESSION['auth']['callbackUrl'],
                        requiredCertificateLevel: CertificateLevel::QUALIFIED,
                        schemeName: AuthCodeCalculator::SCHEME_NAME_DEMO,
                    );

                    $checks[] = ['label' => 'Certificate trust chain', 'ok' => true];
                    $checks[] = ['label' => 'Basic constraints (CA:FALSE)', 'ok' => true];
                    $checks[] = ['label' => 'Certificate policies', 'ok' => true];
                    $checks[] = ['label' => 'ACSP_V2 RSA-PSS signature', 'ok' => true];

                    $response['checks'] = $checks;
                    $response['user'] = [
                        'givenName' => $identity->getGivenName(),
                        'surname' => $identity->getSurname(),
                        'fullName' => $identity->getFullName(),
                        'identityCode' => $identity->getIdentityCode(),
                        'country' => $identity->getCountry(),
                        'dateOfBirth' => $identity->getDateOfBirth()?->format('Y-m-d'),
                        'gender' => $identity->getGender(),
                        'age' => $identity->getAge(),
                    ];

                    $response['documentNumber'] = $status->getResult()->getDocumentNumber();

                } catch (\Sk\SmartId\Exception\ValidationException $e) {
                    $response['endResult'] = 'VALIDATION_ERROR';
                    $response['error'] = $e->getMessage();
                }
            }
        }

        echo json_encode($response);
        exit;
    }

    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart-ID Web2App Authentication</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/web2app.css">
</head>
<body>
    <div class="card">
        <span class="badge">Web2App Flow</span>
        <h1>Log in with Smart-ID</h1>
        <p class="subtitle">Tap the button below to open Smart-ID app on this device</p>

        <div id="auth-container" class="hidden">
            <a href="#" id="smart-id-btn" class="btn">Open Smart-ID App</a>

            <p class="status waiting" id="status">
                <span class="spinner"></span>
                <span>Waiting for authentication...</span>
            </p>
        </div>

        <div id="loading">
            <p class="status waiting">
                <span class="spinner"></span>
                <span>Initializing...</span>
            </p>
        </div>
    </div>

    <script src="js/web2app.js"></script>
</body>
</html>
