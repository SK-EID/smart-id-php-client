<?php


namespace Sk\SmartId\Api\Data;


use Sk\SmartId\Exception\InvalidParametersException;

class Interaction
{

    private static $displayTextAndPINInteractionFlow = "displayTextAndPIN";
    private static $verificationCodeChoiceInteractionFlow = "verificationCodeChoice";
    private static $confirmationMessageInteractionFlow = "confirmationMessage";
    private static $confirmationMessageAndVerificationCodeChoiceInteractionFlow = "confirmationMessageAndVerificationCodeChoice";

    /**
     * One of the above strings must be used as the indicator of the type of the interaction flow used
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $displayText60;

    /**
     * @var string
     */
    private $displayText200;

    /**
     * Interaction constructor.
     * @param string $interactionFlowType
     */
    private function __construct(string $interactionFlowType)
    {
        $this->type = $interactionFlowType;
    }


    public static function ofTypeDisplayTextAndPIN(string $displayText60): Interaction
    {
        $interaction = new Interaction(self::$displayTextAndPINInteractionFlow);
        $interaction->displayText60 = $displayText60;
        return $interaction;
    }

    public static function ofTypeVerificationCodeChoice(string $displayText60): Interaction
    {
        $interaction = new Interaction(self::$verificationCodeChoiceInteractionFlow);
        $interaction->displayText60 = $displayText60;
        return $interaction;
    }

    public static function ofTypeConfirmationMessage(string $displayText200): Interaction
    {
        $interaction = new Interaction(self::$confirmationMessageInteractionFlow);
        $interaction->displayText200 = $displayText200;
        return $interaction;
    }

    public static function ofTypeConfirmationMessageAndVerificationCodeChoice(string $displayText200): Interaction
    {
        $interaction = new Interaction(self::$confirmationMessageAndVerificationCodeChoiceInteractionFlow);
        $interaction->displayText200 = $displayText200;
        return $interaction;
    }

    public function toArray(): array
    {
        $interaction = array(
            "type" => $this->type
        );

        if (isset($this->displayText60))
        {
            $interaction["displayText60"] = $this->displayText60;
        }
        elseif (isset($this->displayText200))
        {
            $interaction["displayText200"] = $this->displayText200;
        }

        return $interaction;
    }

    public function validate()
    {
        if (isset($this->displayText60) and strlen($this->displayText60) > 60)
        {
            throw new InvalidParametersException("Interactions of type displayTextAndPIN and verificationCodeChoice require displayTexts with length 60 or less");
        }

        if (isset($this->displayText200) and strlen($this->displayText200) > 200)
        {
            throw new InvalidParametersException("Interactions of type confirmationMessage and confirmationMessageAndVerificationCodeChoice require displayTexts with length 200 or less");
        }
    }

}

