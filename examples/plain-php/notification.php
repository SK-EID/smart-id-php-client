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

use Sk\SmartId\Ssl\SslPinnedPublicKeyStore;
use Sk\SmartId\Enum\CertificateLevel;
use Sk\SmartId\Enum\SchemeName;
use Sk\SmartId\Notification\NotificationInteraction;
use Sk\SmartId\Model\SemanticsIdentifier;
use Sk\SmartId\Notification\NotificationAuthenticationRequestBuilder;
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
    // ACTION: init - Start a new authentication session with push notification
    // -------------------------------------------------------------------------
    if ($_GET['action'] === 'init') {
        $country = $_GET['country'] ?? 'EE';
        $idCode = $_GET['idCode'] ?? '';
        $documentNumber = $_GET['documentNumber'] ?? '';

        if (empty($idCode) && empty($documentNumber)) {
            ob_end_clean();
            echo json_encode(['error' => 'ID code or document number is required']);
            exit;
        }

        try {
            // Build and initiate the notification authentication request
            $builder = new NotificationAuthenticationRequestBuilder(
                $connector,
                $client->getRelyingPartyUUID(),
                $client->getRelyingPartyName(),
            );

            $interactions = [
                NotificationInteraction::confirmationMessageAndVerificationCodeChoice('Login to Demo'),
                NotificationInteraction::displayTextAndPin('Login to Demo'),
            ];

            // Use document number if provided, otherwise use semantics identifier
            if (!empty($documentNumber)) {
                $builder = $builder->withDocumentNumber($documentNumber);
            } else {
                $semanticsIdentifier = SemanticsIdentifier::fromString("PNO{$country}-{$idCode}");
                $builder = $builder->withSemanticsIdentifier($semanticsIdentifier);
            }

            $session = $builder
                ->withCertificateLevel(CertificateLevel::QUALIFIED)
                ->withAllowedInteractionsOrder($interactions)
                ->initiate();

            // For notification-based flows, manually encode interactions for validation
            $interactionsBase64 = NotificationInteraction::encodeInteractionsToBase64($interactions);

            // Store session data for status polling
            $_SESSION['auth'] = [
                'sessionId' => $session->getSessionId(),
                'verificationCode' => $session->getVerificationCode(),
                'rpChallenge' => $session->getRpChallenge(),
                'rpName' => $client->getRelyingPartyName(),
                'interactionsBase64' => $interactionsBase64,
            ];

            ob_end_clean();
            echo json_encode([
                'success' => true,
                'verificationCode' => $session->getVerificationCode(),
            ]);
        } catch (\Exception $e) {
            ob_end_clean();
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
            ob_end_clean();
            echo json_encode(['error' => 'No session']);
            exit;
        }

        $authData = $_SESSION['auth'];
        session_write_close();

        // Query Smart-ID API for session status
        // timeoutMs enables long polling - server holds connection until status changes or timeout
        $status = $connector->getSessionStatus($authData['sessionId'], timeoutMs: 1000);

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

                    // Validate the authentication response and extract user identity
                    $identity = $validator->validate(
                        $status,
                        $authData['rpChallenge'],
                        $authData['rpName'],
                        $authData['interactionsBase64'],
                        requiredCertificateLevel: CertificateLevel::QUALIFIED,
                        schemeName: $isProduction ? SchemeName::PRODUCTION : SchemeName::DEMO,
                    );

                    // Prevent session fixation: regenerate session ID after successful authentication
                    if (session_status() !== PHP_SESSION_ACTIVE) {
                        session_start();
                    }
                    session_regenerate_id(true);

                    // User information extracted from the certificate
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

                    // Document number for future authentications
                    $response['documentNumber'] = $status->getResult()->getDocumentNumber();

                    // Store certificate PEM in session for download
                    // This can be uploaded to https://demo.sk.ee/upload_cert/ for demo OCSP testing
                    $_SESSION['lastCertPem'] = $status->getCert()->getPemEncodedCertificate();

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

    // -------------------------------------------------------------------------
    // ACTION: download-cert - Download the authentication certificate as PEM
    // After downloading, upload it to https://demo.sk.ee/upload_cert/
    // to make it available in the demo AIA OCSP responder
    // -------------------------------------------------------------------------
    if ($_GET['action'] === 'download-cert') {
        if (!isset($_SESSION['lastCertPem'])) {
            ob_end_clean();
            echo json_encode(['error' => 'No certificate available. Complete authentication first.']);
            exit;
        }

        ob_end_clean();
        header('Content-Type: application/x-pem-file');
        header('Content-Disposition: attachment; filename="smartid_auth_cert.pem"');
        echo $_SESSION['lastCertPem'];
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
    <title>Smart-ID Notification Authentication</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/notification.css">
</head>
<body>
    <div class="card">
        <h1>Log in with Smart-ID</h1>
        <p class="subtitle">Enter your identity to receive a push notification</p>

        <form id="login-form">
            <div class="form-group">
                <label>Identify by</label>
                <div class="toggle-group">
                    <button type="button" class="toggle-btn active" data-mode="identity">Identity Number</button>
                    <button type="button" class="toggle-btn" data-mode="document">Document Number</button>
                </div>
            </div>

            <div id="identity-fields">
                <div class="form-group">
                    <label for="country">Country</label>
                    <select id="country" name="country">
                        <option value="EE">Estonia</option>
                        <option value="LV">Latvia</option>
                        <option value="LT">Lithuania</option>
                        <option value="BE">Belgium</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="idCode">National Identity Number</label>
                    <input type="text" id="idCode" name="idCode" placeholder="e.g., 40504040001">
                </div>
            </div>

            <div id="document-fields" class="hidden">
                <div class="form-group">
                    <label for="documentNumber">Document Number</label>
                    <input type="text" id="documentNumber" name="documentNumber" placeholder="e.g., PNOLT-40504040001-MOCK-Q">
                </div>
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

    <script src="js/utils.js"></script>
    <script src="js/notification.js"></script>
</body>
</html>
