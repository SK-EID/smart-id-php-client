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

namespace Sk\SmartId\Ssl;

class SslPinnedPublicKeyStore
{
    private static function getDemoKeysDir(): string
    {
        return dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'ssl_public_keys';
    }

    /** @var string[] */
    private array $publicKeyHashes = [];

    /**
     * Load demo/test SSL public key hashes for sid.demo.sk.ee.
     * WARNING: Only use this for testing with sid.demo.sk.ee!
     * Never use demo keys in production!
     */
    public static function loadDemo(): self
    {
        $store = new self();
        $store->loadFromDir(self::getDemoKeysDir());

        return $store;
    }

    /**
     * Load SSL public key hashes from a custom directory.
     */
    public static function loadFromDirectory(string $directory): self
    {
        $store = new self();
        $store->loadFromDir($directory);

        return $store;
    }

    /**
     * Create an empty store and add key hashes manually.
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Add a SHA256 public key hash in "sha256//..." format.
     */
    public function addPublicKeyHash(string $hash): self
    {
        $this->publicKeyHashes[] = $hash;

        return $this;
    }

    /**
     * Load all .key files from a directory.
     * Each file should contain a single "sha256//..." hash.
     */
    public function loadFromDir(string $directory): self
    {
        $pattern = rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . '*.key';
        $files = glob($pattern);

        if ($files === false || empty($files)) {
            throw new \RuntimeException("No public key files found in: {$directory}");
        }

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content === false) {
                throw new \RuntimeException("Failed to read public key file: {$file}");
            }

            $hash = trim($content);
            if (!empty($hash)) {
                $this->publicKeyHashes[] = $hash;
            }
        }

        return $this;
    }

    /**
     * @return string[]
     */
    public function getPublicKeyHashes(): array
    {
        return $this->publicKeyHashes;
    }

    /**
     * Returns the pinned key string for CURLOPT_PINNEDPUBLICKEY.
     * Format: "sha256//hash1;sha256//hash2"
     */
    public function toPinnedKeyString(): string
    {
        if (empty($this->publicKeyHashes)) {
            throw new \RuntimeException('No public key hashes configured. Use loadDemo(), loadFromDirectory(), or addPublicKeyHash().');
        }

        return implode(';', $this->publicKeyHashes);
    }
}
