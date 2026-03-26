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
 * 5. Smart-ID app redirects to callback URL
 * 6. Callback page polls session status once and validates the response
 */

require_once __DIR__ . '/vendor/autoload.php';

use Sk\SmartId\Ssl\SslPinnedPublicKeyStore;
use Sk\SmartId\Enum\CertificateLevel;
use Sk\SmartId\Enum\HashAlgorithm;
use Sk\SmartId\Enum\SchemeName;
use Sk\SmartId\DeviceLink\DeviceLinkInteraction;
use Sk\SmartId\Util\CallbackUrlUtil;
use Sk\SmartId\Util\CallbackUrlValidator;
use Sk\SmartId\Util\RpChallengeGenerator;
use Sk\SmartId\Exception\UserRefusedInteractionException;
use Sk\SmartId\Exception\ProtocolFailureException;
use Sk\SmartId\Exception\ServerErrorException;
use Sk\SmartId\Validation\AuthenticationResponseValidator;
use Sk\SmartId\Validation\TrustedCACertificateStore;
use Sk\SmartId\Validation\OcspCertificateRevocationChecker;
use Sk\SmartId\SmartIdClient;

session_start();

// ============================================================================
// CONFIGURATION
// ============================================================================

// Set to true for production environment, false for demo environment
// just for testing purposes
$isProduction = false;

// Smart-ID API endpoint (use production URL for live environment)
$baseUrl = $isProduction ? 'https://rp-api.smart-id.com/v3' : 'https://sid.demo.sk.ee/smart-id-rp/v3';

// Demo Relying Party credentials (replace with your own for production)
$relyingPartyUUID = $isProduction ? '<your-relying-party-uuid>' : '00000000-0000-4000-8000-000000000000';
$relyingPartyName = $isProduction ? '<your-relying-party-name>' : 'DEMO';
$sslPins = $isProduction ? ['sha256//XAlgTJ+3BlgOexKLttcvXfn6Ecu4e2Xr5NyHWnTinKQ='] : ['sha256//Ps1Im3KeB0Q4AlR+/J9KFd/MOznaARdwo4gURPCLaVA='];

// Public HTTPS URL for Web2App callback (required by Smart-ID API)
// Use ngrok or similar tunnel for local development: ngrok http 8080
$publicBaseUrl = 'https://errol-unwithholding-westerly.ngrok-free.dev';

// Initialize the Smart-ID connector with HTTPS pinning
$sslKeys = SslPinnedPublicKeyStore::fromArray($sslPins);
$client = new SmartIdClient($relyingPartyUUID, $relyingPartyName, $baseUrl, $sslKeys);
$connector = $client->getConnector();

// ============================================================================
// AJAX ENDPOINTS
// ============================================================================
if (isset($_GET['action'])) {
    ob_start();
    header('Content-Type: application/json');

    // -------------------------------------------------------------------------
    // ACTION: init - Start a new authentication session
    // -------------------------------------------------------------------------
    if ($_GET['action'] === 'init') {
        $rpChallenge = RpChallengeGenerator::generate();

        // For Web2App, the callback URL must be sent to API during session init!
        // This is required so the Smart-ID backend can validate the authCode
        // CallbackUrlUtil generates a random URL-safe token as the `value` query parameter
        $callbackBase = $publicBaseUrl . '/web2app.php?action=callback';
        CallbackUrlValidator::validateOrThrow($callbackBase);
        $callbackResult = CallbackUrlUtil::createCallbackUrl($callbackBase);
        $callbackUrl = $callbackResult['callbackUrl'];
        $callbackToken = $callbackResult['token'];

        // Define the interactions to be displayed to the user
        $interactions = [DeviceLinkInteraction::displayTextAndPin('Test login')];

        // Use the builder to initiate authentication session with callback URL
        $session = $client->createDeviceLinkAuthentication()
            ->withRpChallenge($rpChallenge)
            ->withHashAlgorithm(HashAlgorithm::SHA512)
            ->withAllowedInteractionsOrder($interactions)
            ->withCertificateLevel(CertificateLevel::QUALIFIED)
            ->withCallbackUrl($callbackUrl)  // Required for Web2App!
            ->initiate();

        // Get the response from the session
        $response = $session->getResponse();

        $_SESSION['auth'] = [
            'sessionId' => $session->getSessionId(),
            'sessionToken' => $response->getSessionToken(),
            'sessionSecret' => $response->getSessionSecret(),
            'deviceLinkBase' => $response->getDeviceLinkBase(),
            'rpChallenge' => $rpChallenge,
            'rpName' => $client->getRelyingPartyName(),
            'interactionsBase64' => $session->getInteractionsBase64(), // Get from session
            'verificationCode' => $session->getVerificationCode(),
            'createdAt' => time(),
            'callbackUrl' => $callbackUrl,   // Store for link generation
            'callbackToken' => $callbackToken, // Store token to verify callback origin
        ];

        ob_end_clean();
        echo json_encode([
            'success' => true,
            'verificationCode' => $session->getVerificationCode(),
        ]);
        exit;
    }

    // -------------------------------------------------------------------------
    // ACTION: link - Get the Web2App deep link URL
    // Unlike QR codes, Web2App links don't need elapsed seconds tracking
    // -------------------------------------------------------------------------
    if ($_GET['action'] === 'link') {
        if (!isset($_SESSION['auth'])) {
            ob_end_clean();
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

        // Reconstruct the session with stored interactionsBase64
        $session = new \Sk\SmartId\DeviceLink\DeviceLinkAuthenticationSession(
            $response,
            $auth['rpChallenge'],
            $auth['rpName'],
            $auth['interactionsBase64'], // Use stored base64 string
            $auth['verificationCode'],
            $auth['callbackUrl'],
        );

        // Build Web2App URL - must use the SAME callback URL sent to API!
        $web2appUrlBuilder = $session->createDeviceLinkBuilder()
            ->withCallbackUrl($auth['callbackUrl']);

        // override to demo env if not in production
        if (!$isProduction) {
            $web2appUrlBuilder = $web2appUrlBuilder->withDemoEnvironment();
        }

        $web2appUrl = $web2appUrlBuilder->buildWeb2AppUrl();

        ob_end_clean();
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
        // 1. `value` must match the random token from CallbackUrlUtil
        // 2. `sessionSecretDigest` must be present (validated later)
        // 3. `userChallengeVerifier` must be present for auth flows
        // =========================================================
        if (!isset($_SESSION['auth'])) {
            ob_end_clean();
            header('Content-Type: text/html; charset=utf-8');
            http_response_code(403);
            echo '<h1>No active session</h1>';
            exit;
        }

        // Step 1: Verify URL token matches the random token we generated
        $urlToken = $_GET['value'] ?? '';
        $expectedToken = $_SESSION['auth']['callbackToken'] ?? '';
        if (!hash_equals($expectedToken, $urlToken)) {
            ob_end_clean();
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
        ob_end_clean();
        header('Content-Type: text/html; charset=utf-8');
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Verifying Authentication...</title>
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <link rel="stylesheet" href="css/callback.css">
        </head>
        <body>
            <div class="card">
                <div id="loading">
                    <div class="spinner"></div>
                    <h1>Verifying authentication...</h1>
                    <p class="subtitle">Polling session status and validating response</p>
                </div>
                <div id="result" class="hidden"></div>
            </div>
            <script src="js/utils.js"></script>
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
                            resultEl.innerHTML = `
                                <div class="success-icon">✓</div>
                                <h1>Authentication Verified!</h1>
                                <div class="user-info">
                                    <p><strong>Name:</strong> ${escapeHtml(data.user.fullName)}</p>
                                    <p><strong>Identity Code:</strong> ${escapeHtml(data.user.identityCode)}</p>
                                    <p><strong>Country:</strong> ${escapeHtml(data.user.country)}</p>
                                    ${data.user.dateOfBirth ? `<p><strong>Date of Birth:</strong> ${escapeHtml(data.user.dateOfBirth)}</p>` : ''}
                                </div>`;
                        } else {
                            resultEl.innerHTML = `
                                <div class="error-icon">✗</div>
                                <h1>Authentication Failed</h1>
                                <p class="subtitle error">
                                    ${escapeHtml(data.error || data.endResult || 'Unknown error')}
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
    // Only called from the callback page after Smart-ID app redirects back.
    // Performs full production-like validation when session is complete.
    // -------------------------------------------------------------------------
    if ($_GET['action'] === 'status') {
        if (!isset($_SESSION['auth'])) {
            ob_end_clean();
            echo json_encode(['error' => 'No session']);
            exit;
        }

        // In Web2App flow, status is only polled from the callback page
        // where callback params (sessionSecretDigest, userChallengeVerifier)
        // have already been stored in the session by the callback handler.
        $authData = $_SESSION['auth'];
        session_write_close();

        $status = $connector->getSessionStatus($authData['sessionId'], timeoutMs: 1000);

        $response = [
            'state' => $status->getState(),
        ];

        if ($status->isComplete() && $status->getResult() !== null) {
            $response['endResult'] = $status->getResult()->getEndResult();

            if ($status->getResult()->isOk()) {
                try {
                    $checks = [];

                    // =========================================================
                    // STEP 1: Verify sessionSecretDigest from callback URL
                    // Proves the callback came from Smart-ID (not spoofed)
                    // Uses CallbackUrlUtil which throws ValidationException on mismatch
                    // =========================================================
                    CallbackUrlUtil::validateSessionSecretDigest(
                        $authData['callbackSessionSecretDigest'],
                        $authData['sessionSecret'],
                    );
                    $checks[] = ['label' => 'Session secret digest', 'ok' => true];

                    // =========================================================
                    // STEP 2: Verify userChallengeVerifier from callback URL
                    // Proves the user actually completed authentication
                    // =========================================================
                    if ($status->getSignature() !== null) {
                        $validator = new AuthenticationResponseValidator();
                        $validator->verifyUserChallenge(
                            $authData['callbackUserChallengeVerifier'],
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

                    // For DEMO environment with uploaded certs - use real AIA OCSP.
                    // First upload your cert to https://demo.sk.ee/upload_cert/
                    // then the AIA OCSP responder (aia.demo.sk.ee) will know your cert.
                    $ocspChecker = OcspCertificateRevocationChecker::create();

                    if (!$isProduction) {
                        TrustedCACertificateStore::loadTestCertificates()->configureValidatorWithOcsp($validator, $ocspChecker);
                    } else {
                        $caStore = TrustedCACertificateStore::create();
                        $caStore->loadFromDefaults()->configureValidatorWithOcsp($validator, $ocspChecker);
                    }

                    $identity = $validator->validate(
                        $status,
                        $authData['rpChallenge'],
                        $authData['rpName'],
                        $authData['interactionsBase64'],
                        $authData['callbackUrl'],
                        requiredCertificateLevel: CertificateLevel::QUALIFIED,
                        schemeName: $isProduction ? SchemeName::PRODUCTION : SchemeName::DEMO,
                    );

                    // Prevent session fixation: regenerate session ID after successful authentication
                    if (session_status() !== PHP_SESSION_ACTIVE) {
                        session_start();
                    }
                    session_regenerate_id(true);

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
                } catch (UserRefusedInteractionException $e) {
                    $response['endResult'] = 'USER_REFUSED_INTERACTION';
                    $response['error'] = 'User refused interaction: ' . ($e->getInteraction() ?? 'unknown');
                } catch (ProtocolFailureException $e) {
                    $response['endResult'] = 'PROTOCOL_FAILURE';
                    $response['error'] = $e->getMessage();
                } catch (ServerErrorException $e) {
                    $response['endResult'] = 'SERVER_ERROR';
                    $response['error'] = $e->getMessage();
                }
            }
        }

        ob_end_clean();
        echo json_encode($response);
        exit;
    }

    ob_end_clean();
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
