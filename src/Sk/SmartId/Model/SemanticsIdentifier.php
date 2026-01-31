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

namespace Sk\SmartId\Model;

class SemanticsIdentifier
{
    public function __construct(
        private readonly string $type,
        private readonly string $countryCode,
        private readonly string $identifier,
    ) {
    }

    public static function fromString(string $semanticsIdentifier): self
    {
        if (!preg_match('/^(PNO|PAS|IDC)([A-Z]{2})-(.+)$/', $semanticsIdentifier, $matches)) {
            throw new \InvalidArgumentException('Invalid semantics identifier format: ' . $semanticsIdentifier);
        }

        return new self($matches[1], $matches[2], $matches[3]);
    }

    public static function forPerson(string $countryCode, string $nationalIdentityNumber): self
    {
        return new self('PNO', strtoupper($countryCode), $nationalIdentityNumber);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function __toString(): string
    {
        return $this->type . $this->countryCode . '-' . $this->identifier;
    }
}
