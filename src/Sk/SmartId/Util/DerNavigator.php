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

namespace Sk\SmartId\Util;

use phpseclib3\File\ASN1;
use Sk\SmartId\Exception\ValidationException;

/**
 * Provides named accessors for navigating DER-encoded ASN.1 structures,
 * replacing brittle magic array indices with descriptive method calls.
 */
class DerNavigator
{
    /**
     * @param array<string, mixed> $node A decoded ASN.1 node from phpseclib's decodeBER()
     */
    private function __construct(private readonly array $node)
    {
    }

    /**
     * Decode a DER binary string and return a navigator for the root element.
     *
     * @throws ValidationException if decoding fails
     */
    public static function fromDer(string $der, string $context = 'DER structure'): self
    {
        $decoded = ASN1::decodeBER($der);
        if (!is_array($decoded) || !isset($decoded[0])) {
            throw new ValidationException(
                sprintf('Failed to decode %s', $context),
            );
        }

        return new self($decoded[0]);
    }

    /**
     * Navigate to a child node by index.
     *
     * @throws ValidationException if the child doesn't exist
     */
    public function child(int $index, string $context = 'child node'): self
    {
        if (!isset($this->node['content'][$index])) {
            throw new ValidationException(
                sprintf('Failed to locate %s at index %d', $context, $index),
            );
        }

        return new self($this->node['content'][$index]);
    }

    /**
     * Get the content value of this node.
     */
    public function content(): mixed
    {
        return $this->node['content'] ?? null;
    }

    /**
     * Extract the raw DER bytes of this node from the original DER input.
     *
     * Uses the node's 'start' offset and 'length' to slice from the original bytes,
     * preserving the exact encoding (unlike re-encoding via ASN1::encodeDER).
     *
     * @throws ValidationException if the node lacks position metadata
     */
    public function extractRawBytes(string $originalDer): string
    {
        if (!isset($this->node['start'], $this->node['length'])) {
            throw new ValidationException('ASN.1 node lacks position metadata for raw byte extraction');
        }

        return substr($originalDer, $this->node['start'], $this->node['length']);
    }

    /**
     * Extract the raw public key bytes from a DER-encoded X.509 certificate.
     *
     * Navigates: Certificate → TBSCertificate → SubjectPublicKeyInfo → SubjectPublicKey (BIT STRING content)
     * and strips the leading unused-bits byte.
     *
     * @throws ValidationException if the expected structure is not found
     */
    public static function extractPublicKeyBytesFromCertDer(string $certDer): string
    {
        $root = self::fromDer($certDer, 'X.509 certificate');
        $bitStringContent = $root
            ->child(0, 'tbsCertificate')
            ->child(6, 'subjectPublicKeyInfo')
            ->child(1, 'subjectPublicKey')
            ->content();

        if (!is_string($bitStringContent) || $bitStringContent === '') {
            throw new ValidationException('Failed to extract public key BIT STRING from certificate');
        }

        // Strip leading unused-bits byte from BIT STRING
        return substr($bitStringContent, 1);
    }
}
