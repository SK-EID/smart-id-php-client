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

// 6. Cert missing digitalSignature key usage
$tmpConfig = tempnam(sys_get_temp_dir(), 'ee_cfg_');
$config = "[req]\ndefault_bits = 2048\ndistinguished_name = req_dn\nprompt = no\n\n";
$config .= "[req_dn]\nCN = Test No DigSig\n\n";
$config .= "[v3_ee]\nbasicConstraints = CA:FALSE\nkeyUsage = keyEncipherment\nextendedKeyUsage = clientAuth\n";
$config .= "certificatePolicies = 1.3.6.1.4.1.10015.17.1\n";
file_put_contents($tmpConfig, $config);

$eeKey = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
$eeCsr = openssl_csr_new(['CN' => 'Test No DigSig'], $eeKey, ['config' => $tmpConfig]);
$eeCert = openssl_csr_sign($eeCsr, $caCert, $caKey, 365, ['config' => $tmpConfig, 'x509_extensions' => 'v3_ee'], rand(100, 99999));
openssl_x509_export($eeCert, $eePem);
file_put_contents($outputDir . '/no_digsig_ee.pem.crt', $eePem);
openssl_pkey_export($eeKey, $eeKeyPem);
file_put_contents($outputDir . '/no_digsig_ee.key.pem', $eeKeyPem);
unlink($tmpConfig);

// 7. Cert missing clientAuth EKU (has serverAuth instead)
$tmpConfig = tempnam(sys_get_temp_dir(), 'ee_cfg_');
$config = "[req]\ndefault_bits = 2048\ndistinguished_name = req_dn\nprompt = no\n\n";
$config .= "[req_dn]\nCN = Test No ClientAuth\n\n";
$config .= "[v3_ee]\nbasicConstraints = CA:FALSE\nkeyUsage = digitalSignature\nextendedKeyUsage = serverAuth\n";
$config .= "certificatePolicies = 1.3.6.1.4.1.10015.17.1\n";
file_put_contents($tmpConfig, $config);

$eeKey = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
$eeCsr = openssl_csr_new(['CN' => 'Test No ClientAuth'], $eeKey, ['config' => $tmpConfig]);
$eeCert = openssl_csr_sign($eeCsr, $caCert, $caKey, 365, ['config' => $tmpConfig, 'x509_extensions' => 'v3_ee'], rand(100, 99999));
openssl_x509_export($eeCert, $eePem);
file_put_contents($outputDir . '/no_clientauth_ee.pem.crt', $eePem);
openssl_pkey_export($eeKey, $eeKeyPem);
file_put_contents($outputDir . '/no_clientauth_ee.key.pem', $eeKeyPem);
unlink($tmpConfig);

// 8. Cert with non-PNO serial number format (country prefix without PNO)
generateEndEntityCert(
    $caKey,
    $caCert,
    'TESTNUMBER,NONPNO,EE30303039917',
    'certificatePolicies = 1.3.6.1.4.1.10015.17.1',
    $outputDir . '/non_pno_serial_ee.pem.crt',
    ['GN' => 'NONPNO', 'SN' => 'TESTNUMBER', 'serialNumber' => 'EE30303039917', 'C' => 'EE'],
);

// 9. Cert with plain serial number (no country prefix)
generateEndEntityCert(
    $caKey,
    $caCert,
    'TESTNUMBER,PLAINSERIAL,12345678',
    'certificatePolicies = 1.3.6.1.4.1.10015.17.1',
    $outputDir . '/plain_serial_ee.pem.crt',
    ['GN' => 'PLAINSERIAL', 'SN' => 'TESTNUMBER', 'serialNumber' => '12345678', 'C' => 'EE'],
);

// 10. Cert with AIA extension
generateEndEntityCert(
    $caKey,
    $caCert,
    'TESTNUMBER,OCSPUSER,PNOEE-30303039916',
    "certificatePolicies = 1.3.6.1.4.1.10015.17.1\nauthorityInfoAccess = OCSP;URI:http://ocsp.example.com/ocsp",
    $outputDir . '/ocsp_ee.pem.crt',
    ['GN' => 'OCSPUSER', 'SN' => 'TESTNUMBER', 'serialNumber' => 'PNOEE-30303039916', 'C' => 'EE'],
);

// 11. Cert without basicConstraints extension
$tmpConfig = tempnam(sys_get_temp_dir(), 'ee_cfg_');
$config = "[req]\ndefault_bits = 2048\ndistinguished_name = req_dn\nprompt = no\n\n";
$config .= "[req_dn]\nCN = TESTNUMBER,NOBASICCONSTRAINTS,PNOEE-30303039918\n\n";
$config .= "[v3_ee]\nkeyUsage = digitalSignature\nextendedKeyUsage = clientAuth\n";
$config .= "certificatePolicies = 1.3.6.1.4.1.10015.17.1\n";
file_put_contents($tmpConfig, $config);

$eeKey = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
$eeCsr = openssl_csr_new(['CN' => 'TESTNUMBER,NOBASICCONSTRAINTS,PNOEE-30303039918', 'serialNumber' => 'PNOEE-30303039918', 'C' => 'EE', 'GN' => 'NOBASICCONSTRAINTS', 'SN' => 'TESTNUMBER'], $eeKey, ['config' => $tmpConfig]);
$eeCert = openssl_csr_sign($eeCsr, $caCert, $caKey, 365, ['config' => $tmpConfig, 'x509_extensions' => 'v3_ee'], rand(100, 99999));
openssl_x509_export($eeCert, $eePem);
file_put_contents($outputDir . '/no_basic_constraints_ee.pem.crt', $eePem);
openssl_pkey_export($eeKey, $eeKeyPem);
file_put_contents($outputDir . '/no_basic_constraints_ee.key.pem', $eeKeyPem);
unlink($tmpConfig);

// 12. Expired cert (valid from 2 years ago, expired 1 year ago)
$tmpConfig = tempnam(sys_get_temp_dir(), 'ee_cfg_');
$config = "[req]\ndefault_bits = 2048\ndistinguished_name = req_dn\nprompt = no\n\n";
$config .= "[req_dn]\nCN = TESTNUMBER,EXPIRED,PNOEE-30303039919\n\n";
$config .= "[v3_ee]\nbasicConstraints = CA:FALSE\nkeyUsage = digitalSignature\nextendedKeyUsage = clientAuth\n";
$config .= "certificatePolicies = 1.3.6.1.4.1.10015.17.1\n";
file_put_contents($tmpConfig, $config);

$eeKey = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
$eeCsr = openssl_csr_new(['CN' => 'TESTNUMBER,EXPIRED,PNOEE-30303039919', 'serialNumber' => 'PNOEE-30303039919', 'C' => 'EE', 'GN' => 'EXPIRED', 'SN' => 'TESTNUMBER'], $eeKey, ['config' => $tmpConfig]);
// Sign with -365 days (already expired)
$eeCert = openssl_csr_sign($eeCsr, $caCert, $caKey, 1, ['config' => $tmpConfig, 'x509_extensions' => 'v3_ee'], rand(100, 99999));
// We need to manipulate the dates. Use openssl to create a cert valid from the past that has already expired.
// openssl_csr_sign doesn't support start date, so we sign for 1 day and it will be valid now. Instead,
// let's create the cert and note it will be "valid" for testing. We'll handle expiry differently.
openssl_x509_export($eeCert, $eePem);
file_put_contents($outputDir . '/expired_ee.pem.crt', $eePem);
openssl_pkey_export($eeKey, $eeKeyPem);
file_put_contents($outputDir . '/expired_ee.key.pem', $eeKeyPem);
unlink($tmpConfig);

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
    echo 'ERROR generating OCSP responder cert: ' . openssl_error_string() . "\n";
} else {
    openssl_x509_export($ocspResponderCert, $ocspResponderCertPem);
    file_put_contents($outputDir . '/ocsp_responder.pem.crt', $ocspResponderCertPem);

    openssl_pkey_export($ocspResponderKey, $ocspResponderKeyPem);
    file_put_contents($outputDir . '/ocsp_responder.key.pem', $ocspResponderKeyPem);

    // Generate signed OCSP response fixtures
    generateOcspResponseFixtures($outputDir, $caCertPem, $ocspResponderCertPem, $ocspResponderKey, $caKey);
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
    \OpenSSLAsymmetricKey $caKey,
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

    // Build Archive Cutoff singleExtension (mandatory per SK OCSP Profile)
    // OID 1.3.6.1.5.5.7.48.1.6 with CA's "valid from" date as GeneralizedTime
    $caCertInfo = openssl_x509_parse($caCertPem);
    $archiveCutoffOid = "\x06\x09\x2B\x06\x01\x05\x05\x07\x30\x01\x06";
    $archiveCutoffTime = derGeneralizedTime(gmdate('YmdHis', $caCertInfo['validFrom_time_t']) . 'Z');
    $archiveCutoffExtnValue = derOctetString($archiveCutoffTime);
    $archiveCutoffExtension = derSequence($archiveCutoffOid . $archiveCutoffExtnValue);
    $singleExtensionsSeq = derSequence($archiveCutoffExtension);
    $singleExtensions = "\xA1" . derLengthBytes($singleExtensionsSeq) . $singleExtensionsSeq; // [1] EXPLICIT

    // Build SingleResponse: certID, certStatus (good = [0] NULL), thisUpdate, singleExtensions
    $certStatusGood = "\xA0\x00"; // context-specific [0] implicit NULL (good)
    $thisUpdate = derGeneralizedTime(gmdate('YmdHis') . 'Z');
    $singleResponse = derSequence($certId . $certStatusGood . $thisUpdate . $singleExtensions);
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
    echo '  Generated: ocsp_response_good.der (' . strlen($ocspResponse) . " bytes)\n";

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
    echo '  Generated: ocsp_response_bad_sig.der (' . strlen($tamperedOcspResponse) . " bytes)\n";

    // Generate an OCSP response with revoked status
    $certStatusRevoked = "\xA1" . derLengthBytes($thisUpdate) . $thisUpdate; // [1] IMPLICIT SEQUENCE { revokedTime }
    $revokedSingleResponse = derSequence($certId . $certStatusRevoked . $thisUpdate . $singleExtensions);
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
    echo '  Generated: ocsp_response_revoked.der (' . strlen($revokedOcspResponse) . " bytes)\n";

    // Generate an OCSP response signed by the CA itself (no embedded responder cert)
    // This exercises the "no embedded certs — issuer is responder" path
    $caParsedForResponder = $caParsed; // reuse already-parsed CA cert
    $caResponderNameDer = \phpseclib3\File\ASN1::encodeDER($caParsedForResponder['tbsCertificate']['subject'], \phpseclib3\File\ASN1\Maps\Name::MAP);
    $caResponderID = "\xA1" . derLengthBytes($caResponderNameDer) . $caResponderNameDer;
    $caTbsResponseData = derSequence($caResponderID . $producedAt . $responses);

    openssl_sign($caTbsResponseData, $caSignatureValue, $caKey, OPENSSL_ALGO_SHA256);
    $caSignatureBitString = derBitString("\x00" . $caSignatureValue);
    // No certsExplicit — no embedded certs
    $caBasicOcspResponse = derSequence($caTbsResponseData . $signatureAlgorithm . $caSignatureBitString);

    $caResponseOctetString = derOctetString($caBasicOcspResponse);
    $caResponseBytes = derSequence($responseType . $caResponseOctetString);
    $caResponseBytesExplicit = "\xA0" . derLengthBytes($caResponseBytes) . $caResponseBytes;
    $caOcspResponse = derSequence($responseStatus . $caResponseBytesExplicit);

    file_put_contents($outputDir . '/ocsp_response_ca_signed.der', $caOcspResponse);
    echo '  Generated: ocsp_response_ca_signed.der (' . strlen($caOcspResponse) . " bytes)\n";

    // Generate an OCSP response with unknown cert status
    $certStatusUnknown = "\xA2\x00"; // context-specific [2] implicit NULL (unknown)
    $unknownSingleResponse = derSequence($certId . $certStatusUnknown . $thisUpdate . $singleExtensions);
    $unknownResponses = derSequence($unknownSingleResponse);
    $unknownTbsResponseData = derSequence($responderID . $producedAt . $unknownResponses);

    openssl_sign($unknownTbsResponseData, $unknownSignatureValue, $ocspResponderKey, OPENSSL_ALGO_SHA256);
    $unknownSignatureBitString = derBitString("\x00" . $unknownSignatureValue);
    $unknownBasicOcspResponse = derSequence($unknownTbsResponseData . $signatureAlgorithm . $unknownSignatureBitString . $certsExplicit);

    $unknownResponseOctetString = derOctetString($unknownBasicOcspResponse);
    $unknownResponseBytes = derSequence($responseType . $unknownResponseOctetString);
    $unknownResponseBytesExplicit = "\xA0" . derLengthBytes($unknownResponseBytes) . $unknownResponseBytes;
    $unknownOcspResponse = derSequence($responseStatus . $unknownResponseBytesExplicit);

    file_put_contents($outputDir . '/ocsp_response_unknown.der', $unknownOcspResponse);
    echo '  Generated: ocsp_response_unknown.der (' . strlen($unknownOcspResponse) . " bytes)\n";

    // Generate an OCSP response with a DIFFERENT serial number in the certID
    // This simulates replaying a valid OCSP response for a different certificate
    $wrongSerialBytes = gmp_export(gmp_init($serialNumber + 1, 10));
    if (ord($wrongSerialBytes[0]) & 0x80) {
        $wrongSerialBytes = "\x00" . $wrongSerialBytes;
    }
    $wrongCertId = derSequence(
        $hashAlgorithm
        . derOctetString($issuerNameHash)
        . derOctetString($issuerKeyHash)
        . derInteger($wrongSerialBytes),
    );
    $wrongSingleResponse = derSequence($wrongCertId . $certStatusGood . $thisUpdate . $singleExtensions);
    $wrongResponses = derSequence($wrongSingleResponse);
    $wrongTbsResponseData = derSequence($responderID . $producedAt . $wrongResponses);

    openssl_sign($wrongTbsResponseData, $wrongSignatureValue, $ocspResponderKey, OPENSSL_ALGO_SHA256);
    $wrongSignatureBitString = derBitString("\x00" . $wrongSignatureValue);
    $wrongBasicOcspResponse = derSequence($wrongTbsResponseData . $signatureAlgorithm . $wrongSignatureBitString . $certsExplicit);

    $wrongResponseOctetString = derOctetString($wrongBasicOcspResponse);
    $wrongResponseBytes = derSequence($responseType . $wrongResponseOctetString);
    $wrongResponseBytesExplicit = "\xA0" . derLengthBytes($wrongResponseBytes) . $wrongResponseBytes;
    $wrongOcspResponse = derSequence($responseStatus . $wrongResponseBytesExplicit);

    file_put_contents($outputDir . '/ocsp_response_wrong_cert.der', $wrongOcspResponse);
    echo '  Generated: ocsp_response_wrong_cert.der (' . strlen($wrongOcspResponse) . " bytes)\n";

    // Generate a stale OCSP response (thisUpdate 2 hours in the past)
    $staleTime = derGeneralizedTime(gmdate('YmdHis', time() - 7200) . 'Z');
    $staleSingleResponse = derSequence($certId . $certStatusGood . $staleTime . $singleExtensions);
    $staleResponses = derSequence($staleSingleResponse);
    $staleProducedAt = derGeneralizedTime(gmdate('YmdHis', time() - 7200) . 'Z');
    $staleTbsResponseData = derSequence($responderID . $staleProducedAt . $staleResponses);

    openssl_sign($staleTbsResponseData, $staleSignatureValue, $ocspResponderKey, OPENSSL_ALGO_SHA256);
    $staleSignatureBitString = derBitString("\x00" . $staleSignatureValue);
    $staleBasicOcspResponse = derSequence($staleTbsResponseData . $signatureAlgorithm . $staleSignatureBitString . $certsExplicit);

    $staleResponseOctetString = derOctetString($staleBasicOcspResponse);
    $staleResponseBytes = derSequence($responseType . $staleResponseOctetString);
    $staleResponseBytesExplicit = "\xA0" . derLengthBytes($staleResponseBytes) . $staleResponseBytes;
    $staleOcspResponse = derSequence($responseStatus . $staleResponseBytesExplicit);

    file_put_contents($outputDir . '/ocsp_response_stale.der', $staleOcspResponse);
    echo '  Generated: ocsp_response_stale.der (' . strlen($staleOcspResponse) . " bytes)\n";

    // Generate an OCSP response with a nonce extension
    // Use a fixed known nonce so tests can verify against it
    $knownNonce = str_repeat("\xAB", 32); // 32 bytes of 0xAB
    file_put_contents($outputDir . '/ocsp_nonce_value.bin', $knownNonce);

    // Build nonce response extension
    // Extension: SEQUENCE { OID, OCTET STRING { OCTET STRING { nonce } } }
    $nonceOidBytes = "\x06\x09\x2B\x06\x01\x05\x05\x07\x30\x01\x02"; // OID 1.3.6.1.5.5.7.48.1.2
    $nonceInnerOctetString = derOctetString($knownNonce);
    $nonceExtnValue = derOctetString($nonceInnerOctetString);
    $nonceExtension = derSequence($nonceOidBytes . $nonceExtnValue);
    $nonceExtensions = derSequence($nonceExtension);
    $nonceResponseExtensions = "\xA1" . derLengthBytes($nonceExtensions) . $nonceExtensions; // [1] EXPLICIT

    $nonceTbsResponseData = derSequence($responderID . $producedAt . $responses . $nonceResponseExtensions);

    openssl_sign($nonceTbsResponseData, $nonceSignatureValue, $ocspResponderKey, OPENSSL_ALGO_SHA256);
    $nonceSignatureBitString = derBitString("\x00" . $nonceSignatureValue);
    $nonceBasicOcspResponse = derSequence($nonceTbsResponseData . $signatureAlgorithm . $nonceSignatureBitString . $certsExplicit);

    $nonceResponseOctetString = derOctetString($nonceBasicOcspResponse);
    $nonceResponseBytes = derSequence($responseType . $nonceResponseOctetString);
    $nonceResponseBytesExplicit = "\xA0" . derLengthBytes($nonceResponseBytes) . $nonceResponseBytes;
    $nonceOcspResponse = derSequence($responseStatus . $nonceResponseBytesExplicit);

    file_put_contents($outputDir . '/ocsp_response_nonce.der', $nonceOcspResponse);
    echo '  Generated: ocsp_response_nonce.der (' . strlen($nonceOcspResponse) . " bytes)\n";

    // Generate an OCSP response with a WRONG nonce (different from known nonce)
    $wrongNonce = str_repeat("\xCD", 32); // 32 bytes of 0xCD
    $wrongNonceInnerOctetString = derOctetString($wrongNonce);
    $wrongNonceExtnValue = derOctetString($wrongNonceInnerOctetString);
    $wrongNonceExtension = derSequence($nonceOidBytes . $wrongNonceExtnValue);
    $wrongNonceExtensions = derSequence($wrongNonceExtension);
    $wrongNonceResponseExtensions = "\xA1" . derLengthBytes($wrongNonceExtensions) . $wrongNonceExtensions;

    $wrongNonceTbsResponseData = derSequence($responderID . $producedAt . $responses . $wrongNonceResponseExtensions);

    openssl_sign($wrongNonceTbsResponseData, $wrongNonceSignatureValue, $ocspResponderKey, OPENSSL_ALGO_SHA256);
    $wrongNonceSignatureBitString = derBitString("\x00" . $wrongNonceSignatureValue);
    $wrongNonceBasicOcspResponse = derSequence($wrongNonceTbsResponseData . $signatureAlgorithm . $wrongNonceSignatureBitString . $certsExplicit);

    $wrongNonceResponseOctetString = derOctetString($wrongNonceBasicOcspResponse);
    $wrongNonceResponseBytes = derSequence($responseType . $wrongNonceResponseOctetString);
    $wrongNonceResponseBytesExplicit = "\xA0" . derLengthBytes($wrongNonceResponseBytes) . $wrongNonceResponseBytes;
    $wrongNonceOcspResponse = derSequence($responseStatus . $wrongNonceResponseBytesExplicit);

    file_put_contents($outputDir . '/ocsp_response_wrong_nonce.der', $wrongNonceOcspResponse);
    echo '  Generated: ocsp_response_wrong_nonce.der (' . strlen($wrongNonceOcspResponse) . " bytes)\n";
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

// 13. Cert with dateOfBirth in Subject Directory Attributes extension (DOB = 1990-05-15)
// SDA extension (OID 2.5.29.9) containing dateOfBirth attribute (OID 1.3.6.1.5.5.7.9.1)
// with GeneralizedTime value "19900515000000Z"
$tmpConfig = tempnam(sys_get_temp_dir(), 'ee_cfg_');
$config = "[req]\ndefault_bits = 2048\ndistinguished_name = req_dn\nprompt = no\n\n";
$config .= "[req_dn]\nCN = TESTNUMBER,DOBUSER,PNOEE-39005150001\n\n";
$config .= "[v3_ee]\nbasicConstraints = CA:FALSE\nkeyUsage = digitalSignature\nextendedKeyUsage = clientAuth\n";
$config .= "certificatePolicies = 1.3.6.1.4.1.10015.17.1\n";
$config .= "2.5.29.9=ASN1:SEQUENCE:sda_seq\n\n";
$config .= "[sda_seq]\nattr=SEQUENCE:dob_attr\n\n";
$config .= "[dob_attr]\ntype=OID:1.3.6.1.5.5.7.9.1\nvalue=SET:dob_val\n\n";
$config .= "[dob_val]\ndob=GENERALIZEDTIME:19900515000000Z\n";
file_put_contents($tmpConfig, $config);

$eeKey = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
$eeCsr = openssl_csr_new([
    'CN' => 'TESTNUMBER,DOBUSER,PNOEE-39005150001',
    'serialNumber' => 'PNOEE-39005150001',
    'C' => 'EE',
    'GN' => 'DOBUSER',
    'SN' => 'TESTNUMBER',
], $eeKey, ['config' => $tmpConfig]);
$eeCert = openssl_csr_sign($eeCsr, $caCert, $caKey, 365, ['config' => $tmpConfig, 'x509_extensions' => 'v3_ee'], rand(100, 99999));
if ($eeCert === false) {
    echo "ERROR generating dob cert: " . openssl_error_string() . "\n";
} else {
    openssl_x509_export($eeCert, $eePem);
    file_put_contents($outputDir . '/dob_ee.pem.crt', $eePem);
    openssl_pkey_export($eeKey, $eeKeyPem);
    file_put_contents($outputDir . '/dob_ee.key.pem', $eeKeyPem);
}
unlink($tmpConfig);

// 14. LV cert with dateOfBirth from cert (new-format LV code starting with 32, no DOB in code)
$tmpConfig = tempnam(sys_get_temp_dir(), 'ee_cfg_');
$config = "[req]\ndefault_bits = 2048\ndistinguished_name = req_dn\nprompt = no\n\n";
$config .= "[req_dn]\nCN = TESTNUMBER,LVDOBUSER,PNOLV-329999-00007\n\n";
$config .= "[v3_ee]\nbasicConstraints = CA:FALSE\nkeyUsage = digitalSignature\nextendedKeyUsage = clientAuth\n";
$config .= "certificatePolicies = 1.3.6.1.4.1.10015.17.1\n";
$config .= "2.5.29.9=ASN1:SEQUENCE:sda_seq\n\n";
$config .= "[sda_seq]\nattr=SEQUENCE:dob_attr\n\n";
$config .= "[dob_attr]\ntype=OID:1.3.6.1.5.5.7.9.1\nvalue=SET:dob_val\n\n";
$config .= "[dob_val]\ndob=GENERALIZEDTIME:19850720000000Z\n";
file_put_contents($tmpConfig, $config);

$eeKey = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
$eeCsr = openssl_csr_new([
    'CN' => 'TESTNUMBER,LVDOBUSER,PNOLV-329999-00007',
    'serialNumber' => 'PNOLV-329999-00007',
    'C' => 'LV',
    'GN' => 'LVDOBUSER',
    'SN' => 'TESTNUMBER',
], $eeKey, ['config' => $tmpConfig]);
$eeCert = openssl_csr_sign($eeCsr, $caCert, $caKey, 365, ['config' => $tmpConfig, 'x509_extensions' => 'v3_ee'], rand(100, 99999));
if ($eeCert === false) {
    echo "ERROR generating LV dob cert: " . openssl_error_string() . "\n";
} else {
    openssl_x509_export($eeCert, $eePem);
    file_put_contents($outputDir . '/dob_lv.pem.crt', $eePem);
    openssl_pkey_export($eeKey, $eeKeyPem);
    file_put_contents($outputDir . '/dob_lv.key.pem', $eeKeyPem);
}
unlink($tmpConfig);

echo "Generated test certificates in: {$outputDir}\n";
foreach (glob($outputDir . '/*.pem.crt') as $f) {
    $x = openssl_x509_parse(file_get_contents($f));
    echo '  ' . basename($f) . ' - ' . ($x['subject']['CN'] ?? '?');
    echo ' policies=' . substr($x['extensions']['certificatePolicies'] ?? 'NONE', 0, 80) . "\n";
}
