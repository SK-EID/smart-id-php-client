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
 * - app2app.php - For native mobile apps (backend API)
 *
 * Flow:
 * 1. User loads the page -> authentication session is initiated
 * 2. QR code is displayed and refreshed every second
 * 3. User scans QR code with Smart-ID app
 * 4. User confirms authentication in the app
 * 5. Page detects completion and shows success message
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
use Sk\SmartId\Util\RpChallengeGenerator;
use Sk\SmartId\Validation\AuthenticationResponseValidator;
use Sk\SmartId\Validation\TrustedCACertificateStore;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

session_start();

// ============================================================================
// CONFIGURATION
// ============================================================================

// Smart-ID API endpoint (use production URL for live environment)
$baseUrl = 'https://sid.demo.sk.ee/smart-id-rp/v3';
// Demo Relying Party credentials (replace with your own for production)
$relyingPartyUUID = '00000000-0000-4000-8000-000000000000';
$relyingPartyName = 'DEMO';

// Initialize the Smart-ID connector with HTTPS pinning
// For production, use SslPinnedPublicKeyStore::loadFromDirectory() or create()->addPublicKeyHash()
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
        // Generate a random challenge (32 bytes, base64 encoded)
        // This is used to create the authentication request and verify the response
        $rpChallenge = RpChallengeGenerator::generate();

        // Build the interactions and store their Base64 representation
        // This exact Base64 value is needed later for response signature verification
        $interactions = [Interaction::displayTextAndPin('Test login')];
        $interactionsBase64 = base64_encode(json_encode(
            array_map(fn (Interaction $i) => $i->toArray(), $interactions),
            JSON_THROW_ON_ERROR,
        ));

        // Build the authentication request
        $request = new DeviceLinkAuthenticationRequest(
            relyingPartyUUID: $relyingPartyUUID,
            relyingPartyName: $relyingPartyName,
            rpChallenge: $rpChallenge,
            hashAlgorithm: HashAlgorithm::SHA512,
            allowedInteractionsOrder: $interactions,
            certificateLevel: CertificateLevel::QUALIFIED,
        );

        // Send the request to Smart-ID API
        $response = $connector->initiateDeviceLinkAuthentication($request);

        // Store session data for subsequent requests (QR refresh, status polling)
        $_SESSION['auth'] = [
            'sessionId' => $response->getSessionID(),
            'sessionToken' => $response->getSessionToken(),
            'sessionSecret' => $response->getSessionSecret(),
            'deviceLinkBase' => $response->getDeviceLinkBase(),
            'rpChallenge' => $rpChallenge,
            'rpName' => $relyingPartyName,
            'interactionsBase64' => $interactionsBase64,
            'createdAt' => time(),
        ];

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
            echo json_encode(['error' => 'No session']);
            exit;
        }

        $auth = $_SESSION['auth'];

        // Reconstruct the response object from stored session data
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
        );

        // Calculate how many seconds have passed since session creation
        // This is crucial - the QR code URL contains a time-based authCode
        $elapsedSeconds = time() - $auth['createdAt'];

        // Build the QR code URL with the current elapsed time
        $qrUrl = $session->createDeviceLinkBuilder()
            ->withElapsedSeconds($elapsedSeconds)
            ->withDemoEnvironment()
            ->buildQrCodeUrl();

        // Generate QR code image using chillerlan/php-qrcode library
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'imageBase64' => true,
            'scale' => 5,
        ]);

        $qrImage = (new QRCode($options))->render($qrUrl);

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
                    // Note: Demo OCSP responder reports test certs as revoked, so don't use OCSP here
                    TrustedCACertificateStore::loadTestCertificates()->configureValidator($validator);

                    // For PRODUCTION environment - use production certificates with OCSP:
                    // TrustedCACertificateStore::loadFromDefaults()->configureValidatorWithOcsp($validator);

                    // Validate the authentication response and extract user identity
                    // This verifies:
                    // - Certificate is signed by a trusted CA
                    // - Certificate is not expired
                    // - Signature is valid
                    // - Optionally: certificate level meets requirements
                    $identity = $validator->validate(
                        $status,
                        $_SESSION['auth']['rpChallenge'],
                        $_SESSION['auth']['rpName'],
                        $_SESSION['auth']['interactionsBase64'],
                        requiredCertificateLevel: CertificateLevel::QUALIFIED,
                        schemeName: AuthCodeCalculator::SCHEME_NAME_DEMO,
                    );

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

                } catch (\Sk\SmartId\Exception\ValidationException $e) {
                    // Validation failed - do not trust this authentication!
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

    <script src="js/index.js"></script>
</body>
</html>
