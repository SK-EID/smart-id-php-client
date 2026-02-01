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
 * Smart-ID Notification Authentication Example
 *
 * This example demonstrates how to implement push notification-based authentication
 * using the Smart-ID Notification flow. The user receives a push notification on their
 * Smart-ID app and must confirm the authentication.
 *
 * Flow:
 * 1. User enters their identity (e.g., national identity number)
 * 2. Authentication session is initiated -> push notification sent to user's device
 * 3. Verification code is displayed to the user
 * 4. User confirms authentication in the Smart-ID app by selecting the matching code
 * 5. Page detects completion and shows success message
 */

require_once __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Sk\SmartId\Api\SmartIdRestConnector;
use Sk\SmartId\Enum\CertificateLevel;
use Sk\SmartId\Model\Interaction;
use Sk\SmartId\Model\SemanticsIdentifier;
use Sk\SmartId\Notification\NotificationAuthenticationRequestBuilder;
use Sk\SmartId\Validation\AuthenticationResponseValidator;
use Sk\SmartId\Validation\TrustedCACertificateStore;

session_start();

// ============================================================================
// CONFIGURATION
// ============================================================================

// Create HTTP client (SSL verification disabled for local testing)
// For production, configure proper CA bundle or set verify => true
$httpClient = new Client(['verify' => false]);
$httpFactory = new HttpFactory();

// Smart-ID API endpoint (use production URL for live environment)
$baseUrl = 'https://sid.demo.sk.ee/smart-id-rp/v3';
// Demo Relying Party credentials (replace with your own for production)
$relyingPartyUUID = '00000000-0000-4000-8000-000000000000';
$relyingPartyName = 'DEMO';

// Initialize the Smart-ID connector with PSR-18 HTTP client and PSR-17 factories
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
    // ACTION: init - Start a new authentication session with push notification
    // -------------------------------------------------------------------------
    if ($_GET['action'] === 'init') {
        $country = $_GET['country'] ?? 'EE';
        $idCode = $_GET['idCode'] ?? '';

        if (empty($idCode)) {
            echo json_encode(['error' => 'ID code is required']);
            exit;
        }

        try {
            // Build the semantics identifier from country and ID code
            $semanticsIdentifier = SemanticsIdentifier::fromString("PNO{$country}-{$idCode}");

            // Build and initiate the notification authentication request
            $builder = new NotificationAuthenticationRequestBuilder(
                $connector,
                $relyingPartyUUID,
                $relyingPartyName,
            );

            $session = $builder
                ->withSemanticsIdentifier($semanticsIdentifier)
                ->withCertificateLevel(CertificateLevel::QUALIFIED)
                ->withAllowedInteractionsOrder([
                    Interaction::confirmationMessageAndVerificationCodeChoice('Login to Demo'),
                    Interaction::displayTextAndPin('Login to Demo'),
                ])
                ->initiate();

            // Store session data for status polling
            $_SESSION['auth'] = [
                'sessionId' => $session->getSessionId(),
                'verificationCode' => $session->getVerificationCode(),
                'rpChallenge' => $session->getRpChallenge(),
            ];

            echo json_encode([
                'success' => true,
                'verificationCode' => $session->getVerificationCode(),
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'error' => $e->getMessage(),
            ]);
        }
        exit;
    }

    // -------------------------------------------------------------------------
    // ACTION: status - Poll for authentication session status
    // Called periodically to check if user has completed authentication
    // -------------------------------------------------------------------------
    if ($_GET['action'] === 'status') {
        if (!isset($_SESSION['auth'])) {
            echo json_encode(['error' => 'No session']);
            exit;
        }

        // Query Smart-ID API for session status
        // timeoutMs enables long polling - server holds connection until status changes or timeout
        $status = $connector->getSessionStatus($_SESSION['auth']['sessionId'], timeoutMs: 1000);

        $response = [
            'state' => $status->getState(),
        ];

        if ($status->isComplete() && $status->getResult() !== null) {
            $response['endResult'] = $status->getResult()->getEndResult();

            // =========================================================
            // EXTRACT USER INFORMATION FROM AUTHENTICATION RESPONSE
            // =========================================================
            if ($status->getResult()->isOk()) {
                try {
                    // Create validator with trusted CA certificates
                    $validator = new AuthenticationResponseValidator();

                    // For DEMO environment (sid.demo.sk.ee) - use TEST certificates
                    TrustedCACertificateStore::loadTestCertificates()->configureValidator($validator);

                    // For PRODUCTION environment - use production certificates:
                    // TrustedCACertificateStore::loadFromDefaults()->configureValidator($validator);

                    // Skip signature verification for quick local testing
                    // WARNING: Enable signature verification in production!
                    $validator->setSkipSignatureVerification(true);

                    // Validate the authentication response and extract user identity
                    $identity = $validator->validate(
                        $status,
                        $_SESSION['auth']['rpChallenge'],
                        CertificateLevel::QUALIFIED,
                    );

                    // User information extracted from the certificate
                    $response['user'] = [
                        'givenName' => $identity->getGivenName(),
                        'surname' => $identity->getSurname(),
                        'identityCode' => $identity->getIdentityCode(),
                        'country' => $identity->getCountry(),
                    ];

                    // Document number for future authentications
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
    <title>Smart-ID Notification Authentication</title>
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
            --border-gray: #E5E7EB;
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
        .form-group {
            margin-bottom: 16px;
            text-align: left;
        }
        label {
            display: block;
            color: var(--text-primary);
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 6px;
        }
        select, input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border-gray);
            border-radius: 10px;
            font-size: 15px;
            font-family: inherit;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        select:focus, input:focus {
            outline: none;
            border-color: var(--smart-id-green);
            box-shadow: 0 0 0 3px var(--smart-id-green-light);
        }
        .btn {
            width: 100%;
            padding: 14px 24px;
            background: var(--smart-id-green);
            color: var(--white);
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 8px;
        }
        .btn:hover {
            background: var(--smart-id-green-dark);
        }
        .btn:disabled {
            background: var(--border-gray);
            cursor: not-allowed;
        }
        .verification-container {
            display: none;
            margin-top: 24px;
        }
        .verification-code {
            font-size: 48px;
            font-weight: 700;
            color: var(--smart-id-green);
            letter-spacing: 8px;
            margin: 16px 0;
        }
        .verification-hint {
            color: var(--text-secondary);
            font-size: 14px;
            line-height: 1.5;
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
        .user-info p:first-child {
            margin-top: 0;
        }
        .user-info p:last-child {
            margin-bottom: 0;
        }
        .error {
            color: #dc2626;
            font-size: 14px;
            margin-top: 12px;
        }
        #login-form.hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>Log in with Smart-ID</h1>
        <p class="subtitle">Enter your identity to receive a push notification</p>

        <form id="login-form">
            <div class="form-group">
                <label for="country">Country</label>
                <select id="country" name="country">
                    <option value="EE">Estonia</option>
                    <option value="LV">Latvia</option>
                    <option value="LT">Lithuania</option>
                </select>
            </div>

            <div class="form-group">
                <label for="idCode">National Identity Number</label>
                <input type="text" id="idCode" name="idCode" placeholder="e.g., 30303039914" required>
            </div>

            <button type="submit" class="btn" id="submit-btn">Continue with Smart-ID</button>
            <p class="error" id="error-message"></p>
        </form>

        <div class="verification-container" id="verification-container">
            <p class="verification-hint">Open Smart-ID app and select the matching code:</p>
            <div class="verification-code" id="verification-code"></div>

            <p class="status waiting" id="status">
                <span class="spinner"></span>
                <span>Waiting for confirmation...</span>
            </p>
        </div>
    </div>

    <script>
        let statusInterval;

        document.getElementById('login-form').addEventListener('submit', async (e) => {
            e.preventDefault();

            const country = document.getElementById('country').value;
            const idCode = document.getElementById('idCode').value;
            const submitBtn = document.getElementById('submit-btn');
            const errorMessage = document.getElementById('error-message');

            errorMessage.textContent = '';
            submitBtn.disabled = true;
            submitBtn.textContent = 'Initiating...';

            try {
                const res = await fetch(`?action=init&country=${encodeURIComponent(country)}&idCode=${encodeURIComponent(idCode)}`);
                const data = await res.json();

                if (data.error) {
                    errorMessage.textContent = data.error;
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Continue with Smart-ID';
                    return;
                }

                if (data.success) {
                    document.getElementById('login-form').classList.add('hidden');
                    document.getElementById('verification-container').style.display = 'block';
                    document.getElementById('verification-code').textContent = data.verificationCode;

                    statusInterval = setInterval(checkStatus, 2000);
                }
            } catch (err) {
                errorMessage.textContent = 'Connection error. Please try again.';
                submitBtn.disabled = false;
                submitBtn.textContent = 'Continue with Smart-ID';
            }
        });

        async function checkStatus() {
            const res = await fetch('?action=status');
            const data = await res.json();

            if (data.state === 'COMPLETE') {
                clearInterval(statusInterval);
                const statusEl = document.getElementById('status');
                statusEl.className = 'status';

                if (data.endResult === 'OK' && data.user) {
                    statusEl.innerHTML = `
                        <span class="success">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            Authentication successful!
                        </span>
                        <div class="user-info">
                            <p><strong>Name:</strong> ${data.user.givenName} ${data.user.surname}</p>
                            <p><strong>Identity Code:</strong> ${data.user.identityCode}</p>
                            <p><strong>Country:</strong> ${data.user.country}</p>
                        </div>`;
                } else if (data.endResult === 'VALIDATION_ERROR') {
                    statusEl.innerHTML = `<span style="color: #dc2626;">Validation failed: ${data.error || 'Unknown error'}</span>`;
                } else {
                    statusEl.innerHTML = `<span style="color: #dc2626;">Authentication failed: ${data.endResult || 'Unknown error'}</span>`;
                }
            }
        }
    </script>
</body>
</html>
