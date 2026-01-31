<?php

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
