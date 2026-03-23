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

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\Model\AuthenticationIdentity;

class AuthenticationIdentityTest extends TestCase
{
    #[Test]
    public function constructorSetsAllProperties(): void
    {
        $identity = new AuthenticationIdentity(
            givenName: 'John',
            surname: 'Doe',
            identityCode: '12345678901',
            country: 'EE',
        );

        $this->assertSame('John', $identity->getGivenName());
        $this->assertSame('Doe', $identity->getSurname());
        $this->assertSame('12345678901', $identity->getIdentityCode());
        $this->assertSame('EE', $identity->getCountry());
    }

    #[Test]
    public function gettersReturnCorrectValues(): void
    {
        $identity = new AuthenticationIdentity(
            givenName: 'Mari',
            surname: 'Maasikas',
            identityCode: '47101010033',
            country: 'LT',
        );

        $this->assertSame('Mari', $identity->getGivenName());
        $this->assertSame('Maasikas', $identity->getSurname());
        $this->assertSame('47101010033', $identity->getIdentityCode());
        $this->assertSame('LT', $identity->getCountry());
    }

    #[Test]
    public function getFullNameReturnsCombinedName(): void
    {
        $identity = new AuthenticationIdentity('John', 'Doe', '12345', 'EE');

        $this->assertSame('John Doe', $identity->getFullName());
    }

    #[Test]
    public function getDateOfBirthReturnsNullForNonBalticCountry(): void
    {
        $identity = new AuthenticationIdentity('John', 'Doe', '12345', 'FI');

        $this->assertNull($identity->getDateOfBirth());
    }

    #[Test]
    public function getDateOfBirthReturnsNullForShortIdentityCode(): void
    {
        $identity = new AuthenticationIdentity('John', 'Doe', '123', 'EE');

        $this->assertNull($identity->getDateOfBirth());
    }

    #[Test]
    public function getDateOfBirthReturnsNullForNonDigitIdentityCode(): void
    {
        $identity = new AuthenticationIdentity('John', 'Doe', 'ABCDEFG', 'EE');

        $this->assertNull($identity->getDateOfBirth());
    }

    #[Test]
    public function getDateOfBirthReturnsCorrectDateFor1800sCentury(): void
    {
        // 1 = male, 1800s; 850101 = 1885-01-01
        $identity = new AuthenticationIdentity('John', 'Doe', '18501010001', 'EE');

        $dob = $identity->getDateOfBirth();
        $this->assertNotNull($dob);
        $this->assertSame('1885-01-01', $dob->format('Y-m-d'));
    }

    #[Test]
    public function getDateOfBirthReturnsCorrectDateFor1900sCentury(): void
    {
        // 3 = male, 1900s; 900515 = 1990-05-15
        $identity = new AuthenticationIdentity('John', 'Doe', '39005150001', 'EE');

        $dob = $identity->getDateOfBirth();
        $this->assertNotNull($dob);
        $this->assertSame('1990-05-15', $dob->format('Y-m-d'));
    }

    #[Test]
    public function getDateOfBirthReturnsCorrectDateFor2000sCentury(): void
    {
        // 5 = male, 2000s; 010101 = 2001-01-01
        $identity = new AuthenticationIdentity('John', 'Doe', '50101010001', 'EE');

        $dob = $identity->getDateOfBirth();
        $this->assertNotNull($dob);
        $this->assertSame('2001-01-01', $dob->format('Y-m-d'));
    }

    #[Test]
    public function getDateOfBirthReturnsCorrectDateFor2100sCentury(): void
    {
        // 7 = male, 2100s; 010101 = 2101-01-01
        $identity = new AuthenticationIdentity('John', 'Doe', '70101010001', 'EE');

        $dob = $identity->getDateOfBirth();
        $this->assertNotNull($dob);
        $this->assertSame('2101-01-01', $dob->format('Y-m-d'));
    }

    #[Test]
    public function getDateOfBirthReturnsNullForGenderCenturyDigit9(): void
    {
        $identity = new AuthenticationIdentity('John', 'Doe', '99005150001', 'EE');

        $this->assertNull($identity->getDateOfBirth());
    }

    #[Test]
    public function getDateOfBirthReturnsNullForGenderCenturyDigit0(): void
    {
        $identity = new AuthenticationIdentity('John', 'Doe', '09005150001', 'EE');

        $this->assertNull($identity->getDateOfBirth());
    }

    #[Test]
    public function getDateOfBirthReturnsNullForInvalidDate(): void
    {
        // 3 = male, 1900s; 001301 = invalid month 13
        $identity = new AuthenticationIdentity('John', 'Doe', '30013010001', 'EE');

        $this->assertNull($identity->getDateOfBirth());
    }

    #[Test]
    public function getDateOfBirthWorksForLatvia(): void
    {
        $identity = new AuthenticationIdentity('John', 'Doe', '39005150001', 'LV');

        $dob = $identity->getDateOfBirth();
        $this->assertNotNull($dob);
        $this->assertSame('1990-05-15', $dob->format('Y-m-d'));
    }

    #[Test]
    public function getDateOfBirthWorksForLithuania(): void
    {
        $identity = new AuthenticationIdentity('John', 'Doe', '39005150001', 'LT');

        $dob = $identity->getDateOfBirth();
        $this->assertNotNull($dob);
        $this->assertSame('1990-05-15', $dob->format('Y-m-d'));
    }

    #[Test]
    public function getDateOfBirthReturnsDateWithZeroTime(): void
    {
        $identity = new AuthenticationIdentity('John', 'Doe', '39005150001', 'EE');

        $dob = $identity->getDateOfBirth();
        $this->assertNotNull($dob);
        $this->assertSame('00:00:00', $dob->format('H:i:s'));
    }

    #[Test]
    public function getGenderReturnsMaleForOddGenderCentury(): void
    {
        // 1 = male (1800s)
        $identity = new AuthenticationIdentity('John', 'Doe', '18501010001', 'EE');
        $this->assertSame('M', $identity->getGender());

        // 3 = male (1900s)
        $identity = new AuthenticationIdentity('John', 'Doe', '39005150001', 'EE');
        $this->assertSame('M', $identity->getGender());

        // 5 = male (2000s)
        $identity = new AuthenticationIdentity('John', 'Doe', '50101010001', 'EE');
        $this->assertSame('M', $identity->getGender());

        // 7 = male (2100s)
        $identity = new AuthenticationIdentity('John', 'Doe', '70101010001', 'EE');
        $this->assertSame('M', $identity->getGender());
    }

    #[Test]
    public function getGenderReturnsFemaleForEvenGenderCentury(): void
    {
        // 2 = female (1800s)
        $identity = new AuthenticationIdentity('Jane', 'Doe', '28501010001', 'EE');
        $this->assertSame('F', $identity->getGender());

        // 4 = female (1900s)
        $identity = new AuthenticationIdentity('Jane', 'Doe', '49005150001', 'EE');
        $this->assertSame('F', $identity->getGender());

        // 6 = female (2000s)
        $identity = new AuthenticationIdentity('Jane', 'Doe', '60101010001', 'EE');
        $this->assertSame('F', $identity->getGender());

        // 8 = female (2100s)
        $identity = new AuthenticationIdentity('Jane', 'Doe', '80101010001', 'EE');
        $this->assertSame('F', $identity->getGender());
    }

    #[Test]
    public function getGenderReturnsNullForNonBalticCountry(): void
    {
        $identity = new AuthenticationIdentity('John', 'Doe', '39005150001', 'FI');

        $this->assertNull($identity->getGender());
    }

    #[Test]
    public function getGenderReturnsNullForEmptyIdentityCode(): void
    {
        $identity = new AuthenticationIdentity('John', 'Doe', '', 'EE');

        $this->assertNull($identity->getGender());
    }

    #[Test]
    public function getGenderReturnsNullForNonDigitFirstChar(): void
    {
        $identity = new AuthenticationIdentity('John', 'Doe', 'A9005150001', 'EE');

        $this->assertNull($identity->getGender());
    }

    #[Test]
    public function getGenderReturnsNullForGenderCenturyDigit9(): void
    {
        $identity = new AuthenticationIdentity('John', 'Doe', '99005150001', 'EE');

        $this->assertNull($identity->getGender());
    }

    #[Test]
    public function getGenderReturnsNullForGenderCenturyDigit0(): void
    {
        $identity = new AuthenticationIdentity('John', 'Doe', '09005150001', 'EE');

        $this->assertNull($identity->getGender());
    }

    #[Test]
    public function getAgeReturnsNullForNonBalticCountry(): void
    {
        $identity = new AuthenticationIdentity('John', 'Doe', '39005150001', 'FI');

        $this->assertNull($identity->getAge());
    }

    #[Test]
    public function getAgeReturnsPositiveIntegerForValidIdentityCode(): void
    {
        // 1990-05-15; should be at least 30+
        $identity = new AuthenticationIdentity('John', 'Doe', '39005150001', 'EE');

        $age = $identity->getAge();
        $this->assertNotNull($age);
        $this->assertGreaterThanOrEqual(35, $age);
    }

    #[Test]
    public function getDateOfBirthForFemale1800s(): void
    {
        // 2 = female, 1800s; 850101 = 1885-01-01
        $identity = new AuthenticationIdentity('Jane', 'Doe', '28501010001', 'EE');

        $dob = $identity->getDateOfBirth();
        $this->assertNotNull($dob);
        $this->assertSame('1885-01-01', $dob->format('Y-m-d'));
    }
}
