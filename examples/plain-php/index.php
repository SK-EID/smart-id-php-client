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
 * Smart-ID QR Code Authentication Example
 *
 * This example demonstrates how to implement QR code-based authentication
 * using the Smart-ID Device Link flow. The QR code must be refreshed every
 * second because it contains a time-based authentication code.
 *
 * Use Case:
 * - User is on a DESKTOP computer browser
 * - User scans the QR code with their phone camera
 * - Smart-ID app opens on the phone
 * - User authenticates in the app
 *
 * Other Device Link Examples:
 * - web2app.php - For mobile web browsers (deep link button)
 * Flow:
 * 1. User loads the page -> authentication session is initiated
 * 2. QR code is displayed and refreshed every second
 * 3. User scans QR code with Smart-ID app
 * 4. User confirms authentication in the app
 * 5. Page detects completion and shows success message
 */

require_once __DIR__ . '/vendor/autoload.php';

use Sk\SmartId\Ssl\SslPinnedPublicKeyStore;
use Sk\SmartId\Enum\CertificateLevel;
use Sk\SmartId\Enum\HashAlgorithm;
use Sk\SmartId\DeviceLink\DeviceLinkInteraction;
use Sk\SmartId\Enum\SchemeName;
use Sk\SmartId\Util\RpChallengeGenerator;
use Sk\SmartId\Exception\UserRefusedInteractionException;
use Sk\SmartId\Exception\ProtocolFailureException;
use Sk\SmartId\Exception\ServerErrorException;
use Sk\SmartId\Validation\AuthenticationResponseValidator;
use Sk\SmartId\Validation\TrustedCACertificateStore;
use Sk\SmartId\Validation\OcspCertificateRevocationChecker;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
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
    // ACTION: init - Start a new authentication session
    // -------------------------------------------------------------------------
    if ($_GET['action'] === 'init') {
        // Generate a random challenge (32 bytes, base64 encoded)
        // This is used to create the authentication request and verify the response
        $rpChallenge = RpChallengeGenerator::generate();

        // Define the interactions to be displayed to the user
        $interactions = [DeviceLinkInteraction::displayTextAndPin('Test login')];

        // Use the builder to initiate authentication session
        $session = $client->createDeviceLinkAuthentication()
            ->withRpChallenge($rpChallenge)
            ->withHashAlgorithm(HashAlgorithm::SHA512)
            ->withAllowedInteractionsOrder($interactions)
            ->withCertificateLevel(CertificateLevel::QUALIFIED)
            ->initiate();

        // Get the response from the session
        $response = $session->getResponse();

        // Store session data for subsequent requests (QR refresh, status polling)
        $_SESSION['auth'] = [
            'sessionId' => $session->getSessionId(),
            'sessionToken' => $response->getSessionToken(),
            'sessionSecret' => $response->getSessionSecret(),
            'deviceLinkBase' => $response->getDeviceLinkBase(),
            'rpChallenge' => $rpChallenge,
            'rpName' => $client->getRelyingPartyName(),
            'interactionsBase64' => $session->getInteractionsBase64(),
            'createdAt' => time(),
        ];

        ob_end_clean();
        echo json_encode([
            'success' => true,
        ]);
        exit;
    }

    // -------------------------------------------------------------------------
    // ACTION: qr - Generate refreshed QR code with updated elapsed time
    // The QR code must be refreshed every second because the authCode changes
    // based on elapsed time to prevent replay attacks
    // -------------------------------------------------------------------------
    if ($_GET['action'] === 'qr') {
        if (!isset($_SESSION['auth'])) {
            ob_end_clean();
            echo json_encode(['error' => 'No session']);
            exit;
        }

        $auth = $_SESSION['auth'];
        session_write_close();

        // Reconstruct the response object from stored session data
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
        );

        // Calculate how many seconds have passed since session creation
        // This is crucial - the QR code URL contains a time-based authCode
        $elapsedSeconds = time() - $auth['createdAt'];

        // Build the QR code URL with the current elapsed time
        $qrUrlBuilder = $session->createDeviceLinkBuilder()
            ->withElapsedSeconds($elapsedSeconds);

        // override to demo env if not in production
        if (!$isProduction) {
            $qrUrlBuilder = $qrUrlBuilder->withDemoEnvironment();
        }

        $qrUrl = $qrUrlBuilder->buildQrCodeUrl();

        // Generate QR code image using chillerlan/php-qrcode library
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'imageBase64' => true,
            'scale' => 5,
        ]);

        $qrImage = (new QRCode($options))->render($qrUrl);

        ob_end_clean();
        echo json_encode([
            'qrImage' => $qrImage,
            'elapsed' => $elapsedSeconds,
        ]);
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

        $authSessionId = $_SESSION['auth']['sessionId'];
        $authData = $_SESSION['auth'];
        session_write_close();

        // Query Smart-ID API for session status
        // timeoutMs enables long polling - server holds connection until status changes or timeout
        $status = $connector->getSessionStatus($authSessionId, timeoutMs: 1000);

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
                    // This verifies:
                    // - Certificate is signed by a trusted CA
                    // - Certificate is not expired
                    // - Signature is valid
                    // - Optionally: certificate level meets requirements
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

                    // You can also get the document number for future authentications
                    $response['documentNumber'] = $status->getResult()->getDocumentNumber();

                    // Store certificate PEM in session for download
                    // This can be uploaded to https://demo.sk.ee/upload_cert/ for demo OCSP testing
                    $_SESSION['lastCertPem'] = $status->getCert()->getPemEncodedCertificate();
                } catch (\Sk\SmartId\Exception\ValidationException $e) {
                    // Validation failed - do not trust this authentication!
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
    <title>Smart-ID Authentication</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/index.css">
</head>
<body>
    <div class="card">
        <h1>Log in with Smart-ID</h1>
        <p class="subtitle">Open Smart-ID app on your phone and scan the QR code</p>

        <div id="qr-container">
            <img id="qr-code" src="" alt="QR Code">
        </div>

        <p class="status waiting" id="status">
            <span class="spinner"></span>
            <span>Waiting for authentication...</span>
        </p>

    </div>

    <script src="js/utils.js"></script>
    <script src="js/index.js"></script>
</body>
</html>
