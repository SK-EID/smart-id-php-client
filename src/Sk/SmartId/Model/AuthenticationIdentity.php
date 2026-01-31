<?php

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

    public function getIdentityCode(): string
    {
        return $this->identityCode;
    }

    public function getCountry(): string
    {
        return $this->country;
    }
}
