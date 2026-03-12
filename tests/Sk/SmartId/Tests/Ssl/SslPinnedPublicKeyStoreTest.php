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

namespace Sk\SmartId\Tests\Ssl;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\Ssl\SslPinnedPublicKeyStore;

class SslPinnedPublicKeyStoreTest extends TestCase
{
    private const VALID_HASH_1 = 'sha256//Ps1Im3KeB0Q4AlR+/J9KFd/MOznaARdwo4gURPCLaVA=';
    private const VALID_HASH_2 = 'sha256//fqp7yWK7iGGKj+3unYdm2DA3VCPDkwtyX+DrdZYSC6o=';

    #[Test]
    public function loadDemoReturnsDemoKeys(): void
    {
        $store = SslPinnedPublicKeyStore::loadDemo();

        $this->assertNotEmpty($store->getPublicKeyHashes());
    }

    #[Test]
    public function createReturnsEmptyStore(): void
    {
        $store = SslPinnedPublicKeyStore::create();

        $this->assertEmpty($store->getPublicKeyHashes());
    }

    #[Test]
    public function addPublicKeyHashAcceptsValidHash(): void
    {
        $store = SslPinnedPublicKeyStore::create()
            ->addPublicKeyHash(self::VALID_HASH_1);

        $this->assertCount(1, $store->getPublicKeyHashes());
        $this->assertSame(self::VALID_HASH_1, $store->getPublicKeyHashes()[0]);
    }

    #[Test]
    public function addPublicKeyHashTrimsWhitespace(): void
    {
        $store = SslPinnedPublicKeyStore::create()
            ->addPublicKeyHash("  " . self::VALID_HASH_1 . "  ");

        $this->assertSame(self::VALID_HASH_1, $store->getPublicKeyHashes()[0]);
    }

    #[Test]
    public function addPublicKeyHashRejectsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Public key hash must not be empty.');

        SslPinnedPublicKeyStore::create()->addPublicKeyHash('');
    }

    #[Test]
    public function addPublicKeyHashRejectsWhitespaceOnly(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Public key hash must not be empty.');

        SslPinnedPublicKeyStore::create()->addPublicKeyHash('   ');
    }

    #[Test]
    public function addPublicKeyHashRejectsMissingPrefix(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid public key hash format');

        SslPinnedPublicKeyStore::create()->addPublicKeyHash('Ps1Im3KeB0Q4AlR+/J9KFd/MOznaARdwo4gURPCLaVA=');
    }

    #[Test]
    public function addPublicKeyHashRejectsInvalidCharacters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid public key hash format');

        SslPinnedPublicKeyStore::create()->addPublicKeyHash('sha256//invalid hash with spaces!');
    }

    #[Test]
    public function fromArrayCreatesStoreFromValidHashes(): void
    {
        $store = SslPinnedPublicKeyStore::fromArray([self::VALID_HASH_1, self::VALID_HASH_2]);

        $this->assertCount(2, $store->getPublicKeyHashes());
        $this->assertSame(self::VALID_HASH_1, $store->getPublicKeyHashes()[0]);
        $this->assertSame(self::VALID_HASH_2, $store->getPublicKeyHashes()[1]);
    }

    #[Test]
    public function fromArrayThrowsOnEmptyArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Public key hash array must not be empty.');

        SslPinnedPublicKeyStore::fromArray([]);
    }

    #[Test]
    public function fromArrayValidatesEachHash(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid public key hash format');

        SslPinnedPublicKeyStore::fromArray([self::VALID_HASH_1, 'bad-hash']);
    }

    #[Test]
    public function fromStringCreatesStoreFromCommaSeparated(): void
    {
        $input = self::VALID_HASH_1 . ',' . self::VALID_HASH_2;

        $store = SslPinnedPublicKeyStore::fromString($input);

        $this->assertCount(2, $store->getPublicKeyHashes());
        $this->assertSame(self::VALID_HASH_1, $store->getPublicKeyHashes()[0]);
        $this->assertSame(self::VALID_HASH_2, $store->getPublicKeyHashes()[1]);
    }

    #[Test]
    public function fromStringHandlesWhitespaceAroundHashes(): void
    {
        $input = "  " . self::VALID_HASH_1 . " , " . self::VALID_HASH_2 . "  ";

        $store = SslPinnedPublicKeyStore::fromString($input);

        $this->assertCount(2, $store->getPublicKeyHashes());
    }

    #[Test]
    public function fromStringAcceptsCustomSeparator(): void
    {
        $input = self::VALID_HASH_1 . ';' . self::VALID_HASH_2;

        $store = SslPinnedPublicKeyStore::fromString($input, ';');

        $this->assertCount(2, $store->getPublicKeyHashes());
    }

    #[Test]
    public function fromStringSingleHash(): void
    {
        $store = SslPinnedPublicKeyStore::fromString(self::VALID_HASH_1);

        $this->assertCount(1, $store->getPublicKeyHashes());
    }

    #[Test]
    public function fromStringThrowsOnEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Public key hash string must not be empty.');

        SslPinnedPublicKeyStore::fromString('');
    }

    #[Test]
    public function fromStringThrowsOnWhitespaceOnly(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Public key hash string must not be empty.');

        SslPinnedPublicKeyStore::fromString('   ');
    }

    #[Test]
    public function fromStringValidatesHashes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid public key hash format');

        SslPinnedPublicKeyStore::fromString(self::VALID_HASH_1 . ',not-a-valid-hash');
    }

    #[Test]
    public function toPinnedKeyStringFormatsCorrectly(): void
    {
        $store = SslPinnedPublicKeyStore::fromArray([self::VALID_HASH_1, self::VALID_HASH_2]);

        $this->assertSame(self::VALID_HASH_1 . ';' . self::VALID_HASH_2, $store->toPinnedKeyString());
    }

    #[Test]
    public function toPinnedKeyStringThrowsWhenEmpty(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No public key hashes configured');

        SslPinnedPublicKeyStore::create()->toPinnedKeyString();
    }

    #[Test]
    public function loadFromDirectoryLoadsKeyFiles(): void
    {
        $dir = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'ssl_public_keys';

        $store = SslPinnedPublicKeyStore::loadFromDirectory($dir);

        $this->assertNotEmpty($store->getPublicKeyHashes());
    }

    #[Test]
    public function loadFromDirectoryThrowsForEmptyDirectory(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No public key files found');

        SslPinnedPublicKeyStore::loadFromDirectory(sys_get_temp_dir());
    }
}
