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
}
