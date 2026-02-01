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
     * Extract date of birth from Estonian/Latvian/Lithuanian identity code.
     * Returns null if the identity code format is not recognized.
     *
     * Identity code format (EE/LV/LT):
     * - 1st digit: gender and century (1-2: 1800s, 3-4: 1900s, 5-6: 2000s)
     * - 2nd-7th digits: YYMMDD (birth date)
     */
    public function getDateOfBirth(): ?\DateTimeImmutable
    {
        if (!in_array($this->country, ['EE', 'LV', 'LT'], true)) {
            return null;
        }

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

        $fullYear = $century . $year;
        $dateString = "{$fullYear}-{$month}-{$day}";

        try {
            $date = \DateTimeImmutable::createFromFormat('Y-m-d', $dateString);
            if ($date === false) {
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
