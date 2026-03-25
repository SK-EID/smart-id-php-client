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

class AuthenticationIdentity
{
    public function __construct(
        private readonly string $givenName,
        private readonly string $surname,
        private readonly string $identityCode,
        private readonly string $country,
        private readonly ?\DateTimeImmutable $dateOfBirthFromCert = null,
    ) {
    }

    public function getGivenName(): string
    {
        return $this->givenName;
    }

    public function getSurname(): string
    {
        return $this->surname;
    }

    public function getFullName(): string
    {
        return $this->givenName . ' ' . $this->surname;
    }

    public function getIdentityCode(): string
    {
        return $this->identityCode;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    /**
     * Get date of birth.
     *
     * First checks if DOB was extracted from the certificate's Subject Directory
     * Attributes extension. If not present, falls back to parsing the national
     * identity number for Baltic countries (EE, LT, and older LV codes).
     *
     * Latvian personal codes issued after July 1st 2017 (starting with "32"-"39")
     * do not carry date-of-birth in the identity code — only the certificate
     * attribute will provide it for those.
     */
    public function getDateOfBirth(): ?\DateTimeImmutable
    {
        if ($this->dateOfBirthFromCert !== null) {
            return $this->dateOfBirthFromCert;
        }

        return match (strtoupper($this->country)) {
            'EE', 'LT' => $this->parseEeLtDateOfBirth(),
            'LV' => $this->parseLvDateOfBirth(),
            default => null,
        };
    }

    /**
     * Parse date of birth from Estonian or Lithuanian identity code.
     *
     * Identity code format (EE/LT):
     * - 1st digit: gender and century (1-2: 1800s, 3-4: 1900s, 5-6: 2000s, 7-8: 2100s)
     * - 2nd-7th digits: YYMMDD (birth date)
     */
    private function parseEeLtDateOfBirth(): ?\DateTimeImmutable
    {
        if (strlen($this->identityCode) < 7 || !ctype_digit(substr($this->identityCode, 0, 7))) {
            return null;
        }

        $genderCentury = (int) $this->identityCode[0];
        $year = substr($this->identityCode, 1, 2);
        $month = substr($this->identityCode, 3, 2);
        $day = substr($this->identityCode, 5, 2);

        $century = match ($genderCentury) {
            1, 2 => '18',
            3, 4 => '19',
            5, 6 => '20',
            7, 8 => '21',
            default => null,
        };

        if ($century === null) {
            return null;
        }

        return $this->parseDateString($century . $year, $month, $day);
    }

    /**
     * Parse date of birth from Latvian identity code.
     *
     * Old LV format: DDMMYY-CXXXX where position 7 (after dash) is century
     * indicator (0=18xx, 1=19xx, 2=20xx).
     *
     * New LV codes (since July 2017) start with 32-39 and do not encode DOB.
     */
    private function parseLvDateOfBirth(): ?\DateTimeImmutable
    {
        if (strlen($this->identityCode) < 8 || !ctype_digit(substr($this->identityCode, 0, 6))) {
            return null;
        }

        $dayPart = substr($this->identityCode, 0, 2);

        // New-format LV codes start with 32-39 and don't carry DOB
        if (preg_match('/^3[2-9]/', $dayPart)) {
            return null;
        }

        $month = substr($this->identityCode, 2, 2);
        $yearTwoDigit = substr($this->identityCode, 4, 2);

        // Century indicator is at position 7 (index 7, after the dash at index 6)
        $centuryIndicator = $this->identityCode[7] ?? null;
        if ($centuryIndicator === null || !ctype_digit($centuryIndicator)) {
            return null;
        }

        $century = match ($centuryIndicator) {
            '0' => '18',
            '1' => '19',
            '2' => '20',
            default => null,
        };

        if ($century === null) {
            return null;
        }

        return $this->parseDateString($century . $yearTwoDigit, $month, $dayPart);
    }

    private function parseDateString(string $fullYear, string $month, string $day): ?\DateTimeImmutable
    {
        $dateString = "{$fullYear}-{$month}-{$day}";

        try {
            $date = \DateTimeImmutable::createFromFormat('Y-m-d', $dateString);
            if ($date === false || $date->format('Y-m-d') !== $dateString) {
                return null;
            }

            return $date->setTime(0, 0, 0);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Get gender from Estonian/Latvian/Lithuanian identity code.
     * Returns 'M' for male, 'F' for female, or null if not determinable.
     */
    public function getGender(): ?string
    {
        if (!in_array($this->country, ['EE', 'LV', 'LT'], true)) {
            return null;
        }

        if (strlen($this->identityCode) < 1 || !ctype_digit($this->identityCode[0])) {
            return null;
        }

        $genderCentury = (int) $this->identityCode[0];

        return match ($genderCentury) {
            1, 3, 5, 7 => 'M',
            2, 4, 6, 8 => 'F',
            default => null,
        };
    }

    /**
     * Calculate age based on date of birth.
     * Returns null if date of birth cannot be determined.
     */
    public function getAge(): ?int
    {
        $dob = $this->getDateOfBirth();
        if ($dob === null) {
            return null;
        }

        $now = new \DateTimeImmutable();

        return $dob->diff($now)->y;
    }
}
