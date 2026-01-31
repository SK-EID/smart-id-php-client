<?php

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
