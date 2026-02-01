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

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Sk\SmartId\Api\SmartIdRestConnector;
use Sk\SmartId\DeviceLink\DeviceLinkAuthenticationRequest;
use Sk\SmartId\DeviceLink\DeviceLinkAuthenticationSession;
use Sk\SmartId\Enum\CertificateLevel;
use Sk\SmartId\Enum\HashAlgorithm;
use Sk\SmartId\Model\Interaction;
use Sk\SmartId\Util\CallbackUrlValidator;
use Sk\SmartId\Util\RpChallengeGenerator;
use Sk\SmartId\Util\VerificationCodeCalculator;
use Sk\SmartId\Validation\AuthenticationResponseValidator;
use Sk\SmartId\Validation\TrustedCACertificateStore;

session_start();

// ============================================================================
// CONFIGURATION
// ============================================================================

$httpClient = new Client(['verify' => false]);
$httpFactory = new HttpFactory();

$baseUrl = 'https://sid.demo.sk.ee/smart-id-rp/v3';
$relyingPartyUUID = '00000000-0000-4000-8000-000000000000';
$relyingPartyName = 'DEMO';

$connector = new SmartIdRestConnector(
    $baseUrl,
    $httpClient,
    $httpFactory,
    $httpFactory,
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
        // IMPORTANT: The API requires HTTPS for callback URL - use ngrok or similar for local testing
        $callbackUrl = 'https://' . $_SERVER['HTTP_HOST'] . strtok($_SERVER['REQUEST_URI'], '?') . '?action=callback';

        $request = new DeviceLinkAuthenticationRequest(
            relyingPartyUUID: $relyingPartyUUID,
            relyingPartyName: $relyingPartyName,
            rpChallenge: $rpChallenge,
            hashAlgorithm: HashAlgorithm::SHA512,
            allowedInteractionsOrder: [
                Interaction::displayTextAndPin('Test login'),
            ],
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
    // -------------------------------------------------------------------------
    if ($_GET['action'] === 'callback') {
        // Override JSON content type for HTML callback page
        header('Content-Type: text/html; charset=utf-8');
        
        $sessionSecretDigest = $_GET['sessionSecretDigest'] ?? null;
        $userChallengeVerifier = $_GET['userChallengeVerifier'] ?? null;
        
        // =====================================================================
        // VALIDATION STEP 1: Verify sessionSecretDigest
        // This proves the callback came from Smart-ID (not spoofed)
        // =====================================================================
        $digestValid = false;
        $auth = $_SESSION['auth'] ?? null;
        if ($auth && $sessionSecretDigest) {
            $digestValid = CallbackUrlValidator::validateSessionSecretDigest(
                $sessionSecretDigest,
                $auth['sessionSecret']
            );
        }
        
        // =====================================================================
        // VALIDATION STEP 2: Verify userChallengeVerifier (after polling completes)
        // This proves the user actually completed the authentication
        // The userChallenge comes from session status response: signature.userChallenge
        // =====================================================================
        // Note: In a real app, you would:
        // 1. Poll session status to get the final response
        // 2. Extract signature.userChallenge from the response
        // 3. Compare: CallbackUrlValidator::validateUserChallengeVerifier(
        //        $userChallengeVerifier,
        //        $sessionStatus->getSignature()->getUserChallenge()
        //    );
        
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Authentication Callback</title>
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
                       background: #0F172A; color: #E2E8F0; padding: 20px; text-align: center; }
                .card { background: #1E293B; border-radius: 16px; padding: 32px; max-width: 400px; margin: 40px auto; }
                .success { color: #22C55E; font-size: 48px; }
                h1 { margin: 16px 0 8px; }
                .param { background: #334155; border-radius: 8px; padding: 12px; margin: 12px 0; 
                         font-family: monospace; font-size: 11px; word-break: break-all; text-align: left; }
                .label { color: #94A3B8; font-size: 12px; margin-bottom: 4px; }
            </style>
        </head>
        <body>
            <div class="card">
                <div class="success">✓</div>
                <h1>Authentication Complete!</h1>
                <p>Smart-ID app redirected back successfully.</p>
                
                <div class="param">
                    <div class="label">sessionSecretDigest:</div>
                    <?= htmlspecialchars($sessionSecretDigest ?? 'N/A') ?>
                    <div style="margin-top: 8px; color: <?= $digestValid ? '#22C55E' : '#EF4444' ?>">
                        <?= $digestValid ? '✓ Valid' : '✗ Invalid or session expired' ?>
                    </div>
                </div>
                <div class="param">
                    <div class="label">userChallengeVerifier:</div>
                    <?= htmlspecialchars($userChallengeVerifier ?? 'N/A') ?>
                    <div style="margin-top: 8px; color: #94A3B8; font-size: 10px;">
                        (Validated against session status response)
                    </div>
                </div>
                
                <p style="margin-top: 24px; color: #94A3B8; font-size: 13px;">
                    In production, also poll session status and validate userChallengeVerifier.
                </p>
            </div>
        </body>
        </html>
        <?php
        exit;
    }

    // -------------------------------------------------------------------------
    // ACTION: status - Poll for authentication session status
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
                try {
                    $validator = new AuthenticationResponseValidator();
                    TrustedCACertificateStore::loadTestCertificates()->configureValidator($validator);

                    // Skip signature verification for quick local testing
                    // WARNING: Enable signature verification in production!
                    $validator->setSkipSignatureVerification(true);

                    $identity = $validator->validate(
                        $status,
                        $_SESSION['auth']['rpChallenge'],
                        CertificateLevel::QUALIFIED,
                    );

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
    <style>
        :root {
            --smart-id-green: #00B955;
            --smart-id-green-dark: #009944;
            --smart-id-green-light: #E6F9EF;
            --text-primary: #1A1A1A;
            --text-secondary: #6B7280;
            --bg-gray: #F5F7FA;
            --white: #FFFFFF;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-gray);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            background: var(--white);
            border-radius: 24px;
            padding: 48px 40px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
            text-align: center;
            max-width: 420px;
            width: 100%;
        }
        .badge {
            display: inline-block;
            background: var(--smart-id-green-light);
            color: var(--smart-id-green-dark);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 16px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        h1 {
            color: var(--text-primary);
            margin-bottom: 8px;
            font-size: 24px;
            font-weight: 600;
        }
        .subtitle {
            color: var(--text-secondary);
            margin-bottom: 32px;
            font-size: 15px;
            line-height: 1.5;
        }
        .verification-code {
            background: var(--bg-gray);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
        }
        .verification-code label {
            display: block;
            color: var(--text-secondary);
            font-size: 13px;
            margin-bottom: 8px;
        }
        .verification-code .code {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: 8px;
        }
        .btn {
            display: block;
            width: 100%;
            padding: 16px 24px;
            background: var(--smart-id-green);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s;
        }
        .btn:hover {
            background: var(--smart-id-green-dark);
        }
        .btn:disabled {
            background: var(--text-secondary);
            cursor: not-allowed;
        }
        .status {
            color: var(--text-secondary);
            margin-top: 24px;
            font-size: 14px;
        }
        .status.waiting {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid var(--bg-gray);
            border-top-color: var(--smart-id-green);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .success {
            color: var(--smart-id-green);
            font-weight: 600;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .success svg {
            width: 24px;
            height: 24px;
        }
        .user-info {
            margin-top: 20px;
            padding: 16px;
            background: var(--smart-id-green-light);
            border-radius: 12px;
            text-align: left;
        }
        .user-info p {
            margin: 8px 0;
            color: var(--text-primary);
            font-size: 14px;
        }
        .user-info p:first-child { margin-top: 0; }
        .user-info p:last-child { margin-bottom: 0; }
        .info-box {
            background: #FEF3C7;
            border: 1px solid #F59E0B;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
            text-align: left;
        }
        .info-box p {
            color: #92400E;
            font-size: 13px;
            line-height: 1.5;
        }
        .hidden { display: none !important; }
    </style>
</head>
<body>
    <div class="card">
        <span class="badge">Web2App Flow</span>
        <h1>Log in with Smart-ID</h1>
        <p class="subtitle">Tap the button below to open Smart-ID app on this device</p>

        <div class="info-box">
            <p><strong>📱 Mobile Only:</strong> This example is for mobile browsers. Tap the button to open the Smart-ID app. After authentication, you'll be redirected back here. <strong>Requires Smart-ID Demo app</strong> for testing.</p>
        </div>

        <div id="auth-container" class="hidden">
            <div class="verification-code">
                <label>Verification code</label>
                <span class="code" id="verification-code">----</span>
            </div>

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

    <script>
        let statusInterval;

        async function init() {
            const res = await fetch('?action=init');
            const data = await res.json();
            
            if (data.success) {
                const linkRes = await fetch('?action=link');
                const linkData = await linkRes.json();
                
                document.getElementById('verification-code').textContent = linkData.verificationCode;
                document.getElementById('smart-id-btn').href = linkData.url;
                
                document.getElementById('loading').classList.add('hidden');
                document.getElementById('auth-container').classList.remove('hidden');
                
                statusInterval = setInterval(checkStatus, 2000);
            }
        }

        async function checkStatus() {
            const res = await fetch('?action=status');
            const data = await res.json();
            
            if (data.state === 'COMPLETE') {
                clearInterval(statusInterval);
                const statusEl = document.getElementById('status');
                statusEl.className = 'status';

                if (data.endResult === 'OK' && data.user) {
                    document.getElementById('smart-id-btn').classList.add('hidden');
                    statusEl.innerHTML = `
                        <span class="success">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            Authentication successful!
                        </span>
                        <div class="user-info">
                            <p><strong>Name:</strong> ${data.user.fullName}</p>
                            <p><strong>Identity Code:</strong> ${data.user.identityCode}</p>
                            <p><strong>Country:</strong> ${data.user.country}</p>
                            ${data.user.dateOfBirth ? `<p><strong>Date of Birth:</strong> ${data.user.dateOfBirth}</p>` : ''}
                            ${data.user.gender ? `<p><strong>Gender:</strong> ${data.user.gender === 'M' ? 'Male' : 'Female'}</p>` : ''}
                            ${data.user.age !== null ? `<p><strong>Age:</strong> ${data.user.age}</p>` : ''}
                        </div>`;
                } else if (data.endResult === 'VALIDATION_ERROR') {
                    statusEl.innerHTML = `<span style="color: #dc2626;">Validation failed: ${data.error || 'Unknown error'}</span>`;
                } else {
                    statusEl.innerHTML = `<span style="color: #dc2626;">Authentication failed: ${data.endResult || 'Unknown error'}</span>`;
                }
            }
        }

        // Only init if not on callback URL
        if (!window.location.search.includes('action=callback')) {
            init();
        }
    </script>
</body>
</html>
