<?php


namespace Sk\SmartId\Api\Data;


use Sk\SmartId\Exception\InvalidParametersException;

class SemanticsIdentifier
{

    /**
     * @var string
     * @described https://www.etsi.org/deliver/etsi_en/319400_319499/31941201/01.01.01_60/en_31941201v010101p.pdf in chapter 5.1.3
     */
    private $semanticsIdentifier;

    /**
     * SemanticsIdentifier constructor.
     * @param string $semanticsIdentifierString
     * @described https://www.etsi.org/deliver/etsi_en/319400_319499/31941201/01.01.01_60/en_31941201v010101p.pdf in chapter 5.1.3
     */
    private function __construct(string $semanticsIdentifierString)
    {
        $this->semanticsIdentifier = $semanticsIdentifierString;
    }

    /**
     * @param string $semanticsIdentifier
     * @described https://www.etsi.org/deliver/etsi_en/319400_319499/31941201/01.01.01_60/en_31941201v010101p.pdf in chapter 5.1.3
     * @return SemanticsIdentifier
     */
    public static function fromString(string $semanticsIdentifier): SemanticsIdentifier
    {
        return new SemanticsIdentifier($semanticsIdentifier);
    }

    public static function builder(): SemanticsIdentifierBuilder
    {
        return new SemanticsIdentifierBuilder();
    }

    public function asString(): string
    {
        return $this->semanticsIdentifier;
    }

    public function validate()
    {
        if (!preg_match("/^[A-Z]{5}-[a-zA-Z\d]{5,30}$/", $this->semanticsIdentifier))
        {
            throw new InvalidParametersException("The semantics identifier format is invalid");
        }
    }

}