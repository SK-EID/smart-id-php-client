<?php

/**
 * Generate test end-entity certificates with specific Certificate Policy OIDs
 * for unit testing verifyCertificatePolicies().
 *
 * Run once: php tests/resources/generate_test_certs.php
 */

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

echo "Generated test certificates in: {$outputDir}\n";
foreach (glob($outputDir . '/*.pem.crt') as $f) {
    $x = openssl_x509_parse(file_get_contents($f));
    echo '  ' . basename($f) . ' - ' . ($x['subject']['CN'] ?? '?');
    echo ' policies=' . substr($x['extensions']['certificatePolicies'] ?? 'NONE', 0, 80) . "\n";
}
