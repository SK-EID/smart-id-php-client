<?php


namespace Sk\SmartId\Api\Data;


class SemanticsIdentifierBuilder
{

    /**
     * @var string
     */
    private $semanticsIdentifierType;

    /**
     * @var string
     */
    private $countryCode;

    /**
     * @var string
     */
    private $identifier;

    public function withSemanticsIdentifierType(string $semanticsIdentifierType): SemanticsIdentifierBuilder
    {
        $this->semanticsIdentifierType = $semanticsIdentifierType;
        return $this;
    }

    public function withCountryCode(string $countryCode): SemanticsIdentifierBuilder
    {
        $this->countryCode = $countryCode;
        return $this;
    }

    public function withIdentifier(string $identityNumber): SemanticsIdentifierBuilder
    {
        $this->identifier = $identityNumber;
        return $this;
    }

    public function build(): SemanticsIdentifier
    {
        return SemanticsIdentifier::fromString($this->semanticsIdentifierType.$this->countryCode.'-'.$this->identifier);
    }

}