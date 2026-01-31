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

namespace Sk\SmartId\Tests\Model;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\Model\SemanticsIdentifier;

class SemanticsIdentifierTest extends TestCase
{
    #[Test]
    public function constructorSetsProperties(): void
    {
        $identifier = new SemanticsIdentifier('PNO', 'EE', '12345678901');

        $this->assertSame('PNO', $identifier->getType());
        $this->assertSame('EE', $identifier->getCountryCode());
        $this->assertSame('12345678901', $identifier->getIdentifier());
    }

    #[Test]
    public function fromStringParsesValidIdentifier(): void
    {
        $identifier = SemanticsIdentifier::fromString('PNOEE-12345678901');

        $this->assertSame('PNO', $identifier->getType());
        $this->assertSame('EE', $identifier->getCountryCode());
        $this->assertSame('12345678901', $identifier->getIdentifier());
    }

    #[Test]
    public function fromStringParsesIdentifierWithDashes(): void
    {
        $identifier = SemanticsIdentifier::fromString('PNOLV-123456-12345');

        $this->assertSame('PNO', $identifier->getType());
        $this->assertSame('LV', $identifier->getCountryCode());
        $this->assertSame('123456-12345', $identifier->getIdentifier());
    }

    #[Test]
    public function fromStringThrowsOnInvalidFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid semantics identifier format');

        SemanticsIdentifier::fromString('invalid');
    }

    #[Test]
    public function fromStringThrowsOnMissingIdentifier(): void
    {
        $this->expectException(InvalidArgumentException::class);

        SemanticsIdentifier::fromString('PNOEE-');
    }

    #[Test]
    public function forPersonCreatesCorrectIdentifier(): void
    {
        $identifier = SemanticsIdentifier::forPerson('EE', '12345678901');

        $this->assertSame('PNO', $identifier->getType());
        $this->assertSame('EE', $identifier->getCountryCode());
        $this->assertSame('12345678901', $identifier->getIdentifier());
    }

    #[Test]
    public function forPersonConvertsCountryCodeToUppercase(): void
    {
        $identifier = SemanticsIdentifier::forPerson('ee', '12345678901');

        $this->assertSame('EE', $identifier->getCountryCode());
    }

    #[Test]
    public function toStringReturnsCorrectFormat(): void
    {
        $identifier = new SemanticsIdentifier('PNO', 'EE', '12345678901');

        $this->assertSame('PNOEE-12345678901', (string) $identifier);
    }

    #[Test]
    public function roundTripFromStringAndToString(): void
    {
        $original = 'PNOEE-12345678901';
        $identifier = SemanticsIdentifier::fromString($original);

        $this->assertSame($original, (string) $identifier);
    }
}
