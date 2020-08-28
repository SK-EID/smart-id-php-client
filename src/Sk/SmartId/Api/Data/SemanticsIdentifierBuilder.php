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

    public function withsemanticsIdentifierType(string $semanticsIdentifierType)
    {
        $this->semanticsIdentifierType = $semanticsIdentifierType;
        return $this;
    }

    public function withCountryCode(string $countryCode)
    {
        $this->countryCode = $countryCode;
        return $this;
    }

    public function withIdentifier(string $identityNumber)
    {
        $this->identifier = $identityNumber;
        return $this;
    }

    public function build()
    {
        return SemanticsIdentifier::fromString($this->semanticsIdentifierType.$this->countryCode.'-'.$this->identifier);
    }

}