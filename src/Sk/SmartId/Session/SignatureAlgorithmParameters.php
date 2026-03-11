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

namespace Sk\SmartId\Session;

use Sk\SmartId\Enum\HashAlgorithm;

class SignatureAlgorithmParameters
{
    public function __construct(
        private readonly string $hashAlgorithm,
        private readonly ?int $saltLength = null,
        private readonly ?string $trailerField = null,
        private readonly ?string $maskGenAlgorithm = null,
        private readonly ?string $maskGenHashAlgorithm = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $hashAlgorithm = '';
        if (isset($data['hashAlgorithm']) && is_string($data['hashAlgorithm'])) {
            $hashAlgorithm = $data['hashAlgorithm'];
        }

        $saltLength = null;
        if (isset($data['saltLength'])) {
            $saltLength = (int) $data['saltLength'];
        }

        $trailerField = null;
        if (isset($data['trailerField']) && is_string($data['trailerField'])) {
            $trailerField = $data['trailerField'];
        }

        $maskGenAlgorithm = null;
        $maskGenHashAlgorithm = null;
        if (isset($data['maskGenAlgorithm']) && is_array($data['maskGenAlgorithm'])) {
            /** @var array<string, mixed> $mgf */
            $mgf = $data['maskGenAlgorithm'];
            if (isset($mgf['algorithm']) && is_string($mgf['algorithm'])) {
                $maskGenAlgorithm = $mgf['algorithm'];
            }
            if (isset($mgf['parameters']) && is_array($mgf['parameters'])) {
                /** @var array<string, mixed> $mgfParams */
                $mgfParams = $mgf['parameters'];
                if (isset($mgfParams['hashAlgorithm']) && is_string($mgfParams['hashAlgorithm'])) {
                    $maskGenHashAlgorithm = $mgfParams['hashAlgorithm'];
                }
            }
        }

        return new self(
            $hashAlgorithm,
            $saltLength,
            $trailerField,
            $maskGenAlgorithm,
            $maskGenHashAlgorithm,
        );
    }

    /**
     * Convert to flat array for backward compatibility.
     *
     * @return array<string, string>
     */
    public function toFlatArray(): array
    {
        $result = ['hashAlgorithm' => $this->hashAlgorithm];

        if ($this->saltLength !== null) {
            $result['saltLength'] = (string) $this->saltLength;
        }

        return $result;
    }

    public function getHashAlgorithm(): string
    {
        return $this->hashAlgorithm;
    }

    public function getResolvedHashAlgorithm(): ?HashAlgorithm
    {
        return HashAlgorithm::fromString($this->hashAlgorithm);
    }

    public function getSaltLength(): ?int
    {
        return $this->saltLength;
    }

    public function getTrailerField(): ?string
    {
        return $this->trailerField;
    }

    public function getMaskGenAlgorithm(): ?string
    {
        return $this->maskGenAlgorithm;
    }

    public function getMaskGenHashAlgorithm(): ?string
    {
        return $this->maskGenHashAlgorithm;
    }
}
