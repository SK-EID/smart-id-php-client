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

declare(strict_types=1);

namespace Sk\SmartId\Validation;

class TrustedCACertificateStore
{
    private static function getDefaultCertificatesDir(): string
    {
        // From src/Sk/SmartId/Validation, go up 3 levels to src/
        return dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'trusted_certificates';
    }

    private static function getTestCertificatesDir(): string
    {
        // From src/Sk/SmartId/Validation, go up 4 levels to project root, then tests/resources
        return dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'trusted_certificates';
    }

    /** @var string[] PEM-encoded certificate contents */
    private array $certificates = [];

    /** @var string[] Absolute file paths to certificate files */
    private array $certificateFilePaths = [];

    /**
     * Load certificates from the default location bundled with the SDK.
     * This includes production Smart-ID CA certificates.
     */
    public static function loadFromDefaults(): self
    {
        $store = new self();
        $store->loadFromDir(self::getDefaultCertificatesDir());

        return $store;
    }

    /**
     * Load TEST certificates for demo/test environment.
     * WARNING: Only use this for testing with sid.demo.sk.ee!
     * Never use test certificates in production!
     */
    public static function loadTestCertificates(): self
    {
        $store = new self();
        $store->loadFromDir(self::getTestCertificatesDir());

        return $store;
    }

    /**
     * Load certificates from a custom directory.
     */
    public static function loadFromDirectory(string $directory): self
    {
        $store = new self();
        $store->loadFromDir($directory);

        return $store;
    }

    /**
     * Create an empty store and add certificates manually.
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Add a PEM-encoded certificate.
     */
    public function addCertificate(string $pemCertificate): self
    {
        $this->certificates[] = $pemCertificate;

        return $this;
    }

    /**
     * Add a certificate from a file path.
     */
    public function addCertificateFromFile(string $filePath): self
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException("Failed to read certificate file: {$filePath}");
        }

        $this->certificates[] = $content;
        $this->certificateFilePaths[] = $filePath;

        return $this;
    }

    /**
     * Load all .pem.crt files from a directory.
     */
    public function loadFromDir(string $directory): self
    {
        $pattern = rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . '*.pem.crt';
        $files = glob($pattern);

        if ($files === false || empty($files)) {
            throw new \RuntimeException("No certificate files found in: {$directory}");
        }

        foreach ($files as $file) {
            $this->addCertificateFromFile($file);
        }

        return $this;
    }

    /**
     * @return string[]
     */
    public function getCertificates(): array
    {
        return $this->certificates;
    }

    /**
     * @return string[]
     */
    public function getCertificateFilePaths(): array
    {
        return $this->certificateFilePaths;
    }

    /**
     * Configure an AuthenticationResponseValidator with these certificates.
     */
    public function configureValidator(AuthenticationResponseValidator $validator): AuthenticationResponseValidator
    {
        $validator->setTrustedCaCertificates($this->certificates);
        $validator->setTrustedCaCertificateFiles($this->certificateFilePaths);

        return $validator;
    }

    public function configureValidatorWithOcsp(
        AuthenticationResponseValidator $validator,
        OcspCertificateRevocationChecker $ocspChecker,
    ): AuthenticationResponseValidator {
        $this->configureValidator($validator);
        $validator->setOcspRevocationChecker($ocspChecker);

        return $validator;
    }
}
