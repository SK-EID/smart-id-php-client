<?php

/**
 * Generate test end-entity certificates with specific Certificate Policy OIDs
 * for unit testing verifyCertificatePolicies().
 *
 * Run once: php tests/resources/generate_test_certs.php
 */

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

$outputDir = __DIR__ . '/test_end_entity_certs';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0o755, true);
}

// Write CA config
$caConfigFile = tempnam(sys_get_temp_dir(), 'ca_cfg_');
file_put_contents($caConfigFile, <<<'CONF'
    [req]
    default_bits = 2048
    distinguished_name = req_dn
    prompt = no

    [req_dn]
    CN = Test CA
    O = Test

    [v3_ca]
    basicConstraints = critical, CA:TRUE
    keyUsage = keyCertSign, cRLSign
    subjectKeyIdentifier = hash
    CONF);

// Generate a CA key pair and self-signed cert
$caKey = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
$caCsr = openssl_csr_new(['CN' => 'Test CA', 'O' => 'Test'], $caKey, ['config' => $caConfigFile]);
$caCert = openssl_csr_sign($caCsr, null, $caKey, 3650, ['config' => $caConfigFile, 'x509_extensions' => 'v3_ca'], 1);
unlink($caConfigFile);

// Export CA cert
openssl_x509_export($caCert, $caCertPem);
file_put_contents($outputDir . '/test_ca.pem.crt', $caCertPem);

// Helper: generate end-entity cert with specific config
function generateEndEntityCert(
    $caKey,
    $caCert,
    string $cn,
    string $configExtra,
    string $outputPath,
    array $extraSubject = [],
): void {
    $tmpConfig = tempnam(sys_get_temp_dir(), 'ee_cfg_');
    $config = "[req]\ndefault_bits = 2048\ndistinguished_name = req_dn\nprompt = no\n\n";
    $config .= "[req_dn]\nCN = {$cn}\n\n";
    $config .= "[v3_ee]\nbasicConstraints = CA:FALSE\nkeyUsage = digitalSignature\nextendedKeyUsage = clientAuth\n";
    if ($configExtra !== '') {
        $config .= $configExtra . "\n";
    }
    file_put_contents($tmpConfig, $config);

    $dn = array_merge(['CN' => $cn], $extraSubject);
    $eeKey = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    $eeCsr = openssl_csr_new($dn, $eeKey, ['config' => $tmpConfig]);
    $eeCert = openssl_csr_sign($eeCsr, $caCert, $caKey, 365, ['config' => $tmpConfig, 'x509_extensions' => 'v3_ee'], rand(100, 99999));

    if ($eeCert === false) {
        echo "ERROR generating cert for {$cn}: " . openssl_error_string() . "\n";
        unlink($tmpConfig);

        return;
    }

    openssl_x509_export($eeCert, $eeCertPem);
    file_put_contents($outputPath, $eeCertPem);

    // Also export private key for ACSP_V2 signature generation in tests
    openssl_pkey_export($eeKey, $eeKeyPem);
    $keyPath = preg_replace('/\.pem\.crt$/', '.key.pem', $outputPath);
    file_put_contents($keyPath, $eeKeyPem);

    unlink($tmpConfig);
}

// 1. Cert with Qualified Smart-ID policy OIDs
generateEndEntityCert(
    $caKey,
    $caCert,
    'TESTNUMBER,QUALIFIED,PNOEE-30303039914',
    'certificatePolicies = 1.3.6.1.4.1.10015.17.2, 0.4.0.2042.1.2',
    $outputDir . '/qualified_smartid_ee.pem.crt',
    ['GN' => 'QUALIFIED', 'SN' => 'TESTNUMBER', 'serialNumber' => 'PNOEE-30303039914', 'C' => 'EE'],
);

// 2. Cert with Non-Qualified Smart-ID policy OID
generateEndEntityCert(
    $caKey,
    $caCert,
    'TESTNUMBER,NONQUALIFIED,PNOEE-30303039915',
    'certificatePolicies = 1.3.6.1.4.1.10015.17.1',
    $outputDir . '/non_qualified_smartid_ee.pem.crt',
    ['GN' => 'NONQUALIFIED', 'SN' => 'TESTNUMBER', 'serialNumber' => 'PNOEE-30303039915', 'C' => 'EE'],
);

// 3. Cert with NO Smart-ID policy OIDs (e.g. Mobile-ID)
generateEndEntityCert(
    $caKey,
    $caCert,
    'Test Non-SmartID User',
    'certificatePolicies = 1.3.6.1.4.1.10015.1.3',
    $outputDir . '/non_smartid_ee.pem.crt',
);

// 4. Cert with Qualified OID but missing EU QCP OID
generateEndEntityCert(
    $caKey,
    $caCert,
    'Test Qualified Missing EU QCP',
    'certificatePolicies = 1.3.6.1.4.1.10015.17.2',
    $outputDir . '/qualified_missing_euqcp_ee.pem.crt',
);

// 5. Cert with no policies at all
generateEndEntityCert(
    $caKey,
    $caCert,
    'Test No Policies User',
    '',
    $outputDir . '/no_policies_ee.pem.crt',
);

// 6. Cert with AIA extension
generateEndEntityCert(
    $caKey,
    $caCert,
    'TESTNUMBER,OCSPUSER,PNOEE-30303039916',
    "certificatePolicies = 1.3.6.1.4.1.10015.17.1\nauthorityInfoAccess = OCSP;URI:http://ocsp.example.com/ocsp",
    $outputDir . '/ocsp_ee.pem.crt',
    ['GN' => 'OCSPUSER', 'SN' => 'TESTNUMBER', 'serialNumber' => 'PNOEE-30303039916', 'C' => 'EE'],
);

// 7. OCSP Responder certificate (with id-kp-OCSPSigning EKU, signed by CA)
$ocspResponderConfigFile = tempnam(sys_get_temp_dir(), 'ocsp_cfg_');
file_put_contents($ocspResponderConfigFile, <<<'CONF'
    [req]
    default_bits = 2048
    distinguished_name = req_dn
    prompt = no

    [req_dn]
    CN = Test OCSP Responder

    [v3_ocsp]
    basicConstraints = CA:FALSE
    keyUsage = digitalSignature
    extendedKeyUsage = OCSPSigning
    CONF);

$ocspResponderKey = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
$ocspResponderCsr = openssl_csr_new(['CN' => 'Test OCSP Responder'], $ocspResponderKey, ['config' => $ocspResponderConfigFile]);
$ocspResponderCert = openssl_csr_sign($ocspResponderCsr, $caCert, $caKey, 365, ['config' => $ocspResponderConfigFile, 'x509_extensions' => 'v3_ocsp'], rand(100, 99999));
unlink($ocspResponderConfigFile);

if ($ocspResponderCert === false) {
    echo "ERROR generating OCSP responder cert: " . openssl_error_string() . "\n";
} else {
    openssl_x509_export($ocspResponderCert, $ocspResponderCertPem);
    file_put_contents($outputDir . '/ocsp_responder.pem.crt', $ocspResponderCertPem);

    openssl_pkey_export($ocspResponderKey, $ocspResponderKeyPem);
    file_put_contents($outputDir . '/ocsp_responder.key.pem', $ocspResponderKeyPem);

    // Generate signed OCSP response fixtures
    generateOcspResponseFixtures($outputDir, $caCertPem, $ocspResponderCertPem, $ocspResponderKey);
}

/**
 * Generate signed OCSP response DER fixtures for unit testing.
 * Builds BasicOCSPResponse with proper tbsResponseData, signature, and embedded responder cert.
 */
function generateOcspResponseFixtures(
    string $outputDir,
    string $caCertPem,
    string $ocspResponderCertPem,
    \OpenSSLAsymmetricKey $ocspResponderKey,
): void {
    // Read the ocsp_ee certificate to get its serial number and issuer info
    $eeCertPem = file_get_contents($outputDir . '/ocsp_ee.pem.crt');
    $eeCertInfo = openssl_x509_parse($eeCertPem);
    $serialNumber = $eeCertInfo['serialNumber'];

    // Compute issuer name hash and issuer key hash (SHA-1)
    $caCertDer = pemToDer($caCertPem);
    $caDecoded = \phpseclib3\File\ASN1::decodeBER($caCertDer);
    $caParsed = \phpseclib3\File\ASN1::asn1map($caDecoded[0], \phpseclib3\File\ASN1\Maps\Certificate::MAP);
    $issuerNameDer = \phpseclib3\File\ASN1::encodeDER($caParsed['tbsCertificate']['subject'], \phpseclib3\File\ASN1\Maps\Name::MAP);
    $issuerNameHash = sha1($issuerNameDer, true);

    // Extract raw public key bytes from CA cert
    $caKeyResource = openssl_pkey_get_public($caCertPem);
    $caKeyDetails = openssl_pkey_get_details($caKeyResource);
    $caPublicKeyDer = pemToDer($caKeyDetails['key']);
    $caKeyDecoded = \phpseclib3\File\ASN1::decodeBER($caPublicKeyDer);
    $issuerKeyBytes = substr($caKeyDecoded[0]['content'][1]['content'], 1);
    $issuerKeyHash = sha1($issuerKeyBytes, true);

    // Build CertID
    $sha1Oid = "\x06\x05\x2B\x0E\x03\x02\x1A"; // OID 1.3.14.3.2.26 (SHA-1)
    $hashAlgorithm = derSequence($sha1Oid . "\x05\x00"); // AlgorithmIdentifier with NULL params

    $serialBytes = gmp_export(gmp_init($serialNumber, 10));
    if (ord($serialBytes[0]) & 0x80) {
        $serialBytes = "\x00" . $serialBytes;
    }

    $certId = derSequence(
        $hashAlgorithm
        . derOctetString($issuerNameHash)
        . derOctetString($issuerKeyHash)
        . derInteger($serialBytes),
    );

    // Build SingleResponse: certID, certStatus (good = [0] NULL), thisUpdate
    $certStatusGood = "\xA0\x00"; // context-specific [0] implicit NULL (good)
    $thisUpdate = derGeneralizedTime(gmdate('YmdHis') . 'Z');
    $singleResponse = derSequence($certId . $certStatusGood . $thisUpdate);
    $responses = derSequence($singleResponse);

    // Build ResponderID (byName)
    $responderCertInfo = openssl_x509_parse($ocspResponderCertPem);
    $responderCertDer = pemToDer($ocspResponderCertPem);
    $responderDecoded = \phpseclib3\File\ASN1::decodeBER($responderCertDer);
    $responderParsed = \phpseclib3\File\ASN1::asn1map($responderDecoded[0], \phpseclib3\File\ASN1\Maps\Certificate::MAP);
    $responderNameDer = \phpseclib3\File\ASN1::encodeDER($responderParsed['tbsCertificate']['subject'], \phpseclib3\File\ASN1\Maps\Name::MAP);
    $responderID = "\xA1" . derLengthBytes($responderNameDer) . $responderNameDer; // [1] EXPLICIT Name

    // producedAt
    $producedAt = derGeneralizedTime(gmdate('YmdHis') . 'Z');

    // tbsResponseData (no version field = default v1)
    $tbsResponseData = derSequence($responderID . $producedAt . $responses);

    // Sign tbsResponseData with OCSP responder key
    openssl_sign($tbsResponseData, $signatureValue, $ocspResponderKey, OPENSSL_ALGO_SHA256);

    // signatureAlgorithm: sha256WithRSAEncryption
    $sha256WithRsaOid = "\x06\x09\x2A\x86\x48\x86\xF7\x0D\x01\x01\x0B"; // OID 1.2.840.113549.1.1.11
    $signatureAlgorithm = derSequence($sha256WithRsaOid . "\x05\x00");

    // signature as BIT STRING (prepend 0x00 unused bits byte)
    $signatureBitString = derBitString("\x00" . $signatureValue);

    // Embedded responder certificate
    $certsSeq = derSequence($responderCertDer);
    $certsExplicit = "\xA0" . derLengthBytes($certsSeq) . $certsSeq; // [0] EXPLICIT

    // BasicOCSPResponse
    $basicOcspResponse = derSequence($tbsResponseData . $signatureAlgorithm . $signatureBitString . $certsExplicit);

    // Build outer OCSPResponse
    $responseStatus = "\x0A\x01\x00"; // ENUMERATED 0 (successful)
    $responseType = "\x06\x09\x2B\x06\x01\x05\x05\x07\x30\x01\x01"; // OID 1.3.6.1.5.5.7.48.1.1 (id-pkix-ocsp-basic)
    $responseOctetString = derOctetString($basicOcspResponse);
    $responseBytes = derSequence($responseType . $responseOctetString);
    $responseBytesExplicit = "\xA0" . derLengthBytes($responseBytes) . $responseBytes;

    $ocspResponse = derSequence($responseStatus . $responseBytesExplicit);

    file_put_contents($outputDir . '/ocsp_response_good.der', $ocspResponse);
    echo "  Generated: ocsp_response_good.der (" . strlen($ocspResponse) . " bytes)\n";

    // Generate an OCSP response with a tampered signature (for testing verification failure)
    $tamperedSignature = $signatureValue;
    $tamperedSignature[0] = chr(ord($tamperedSignature[0]) ^ 0xFF);
    $tamperedSignatureBitString = derBitString("\x00" . $tamperedSignature);
    $tamperedBasicOcspResponse = derSequence($tbsResponseData . $signatureAlgorithm . $tamperedSignatureBitString . $certsExplicit);

    $tamperedResponseOctetString = derOctetString($tamperedBasicOcspResponse);
    $tamperedResponseBytes = derSequence($responseType . $tamperedResponseOctetString);
    $tamperedResponseBytesExplicit = "\xA0" . derLengthBytes($tamperedResponseBytes) . $tamperedResponseBytes;
    $tamperedOcspResponse = derSequence($responseStatus . $tamperedResponseBytesExplicit);

    file_put_contents($outputDir . '/ocsp_response_bad_sig.der', $tamperedOcspResponse);
    echo "  Generated: ocsp_response_bad_sig.der (" . strlen($tamperedOcspResponse) . " bytes)\n";

    // Generate an OCSP response with revoked status
    $certStatusRevoked = "\xA1" . derLengthBytes($thisUpdate) . $thisUpdate; // [1] IMPLICIT SEQUENCE { revokedTime }
    $revokedSingleResponse = derSequence($certId . $certStatusRevoked . $thisUpdate);
    $revokedResponses = derSequence($revokedSingleResponse);
    $revokedTbsResponseData = derSequence($responderID . $producedAt . $revokedResponses);

    openssl_sign($revokedTbsResponseData, $revokedSignatureValue, $ocspResponderKey, OPENSSL_ALGO_SHA256);
    $revokedSignatureBitString = derBitString("\x00" . $revokedSignatureValue);
    $revokedBasicOcspResponse = derSequence($revokedTbsResponseData . $signatureAlgorithm . $revokedSignatureBitString . $certsExplicit);

    $revokedResponseOctetString = derOctetString($revokedBasicOcspResponse);
    $revokedResponseBytes = derSequence($responseType . $revokedResponseOctetString);
    $revokedResponseBytesExplicit = "\xA0" . derLengthBytes($revokedResponseBytes) . $revokedResponseBytes;
    $revokedOcspResponse = derSequence($responseStatus . $revokedResponseBytesExplicit);

    file_put_contents($outputDir . '/ocsp_response_revoked.der', $revokedOcspResponse);
    echo "  Generated: ocsp_response_revoked.der (" . strlen($revokedOcspResponse) . " bytes)\n";
}

function pemToDer(string $pem): string
{
    $base64 = preg_replace('/-----[A-Z ]+-----/', '', $pem);

    return base64_decode(str_replace(["\r", "\n", ' '], '', $base64 ?? ''), true) ?: '';
}

function derSequence(string $content): string
{
    return "\x30" . derLengthBytes($content) . $content;
}

function derOctetString(string $value): string
{
    return "\x04" . derLengthBytes($value) . $value;
}

function derBitString(string $value): string
{
    return "\x03" . derLengthBytes($value) . $value;
}

function derInteger(string $bytes): string
{
    if (strlen($bytes) > 0 && (ord($bytes[0]) & 0x80) !== 0) {
        $bytes = "\x00" . $bytes;
    }

    return "\x02" . derLengthBytes($bytes) . $bytes;
}

function derGeneralizedTime(string $time): string
{
    return "\x18" . derLengthBytes($time) . $time;
}

function derLengthBytes(string $content): string
{
    $len = strlen($content);
    if ($len < 0x80) {
        return chr($len);
    }
    if ($len < 0x100) {
        return "\x81" . chr($len);
    }
    if ($len < 0x10000) {
        return "\x82" . pack('n', $len);
    }

    return "\x83" . chr(($len >> 16) & 0xFF) . pack('n', $len & 0xFFFF);
}

echo "Generated test certificates in: {$outputDir}\n";
foreach (glob($outputDir . '/*.pem.crt') as $f) {
    $x = openssl_x509_parse(file_get_contents($f));
    echo '  ' . basename($f) . ' - ' . ($x['subject']['CN'] ?? '?');
    echo ' policies=' . substr($x['extensions']['certificatePolicies'] ?? 'NONE', 0, 80) . "\n";
}
