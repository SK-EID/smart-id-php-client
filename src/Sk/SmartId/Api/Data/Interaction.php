<?php


namespace Sk\SmartId\Api\Data;


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


    private static function ofTypeDisplayTextAndPIN(string $displayText60)
    {
        $interaction = new Interaction(self::$displayTextAndPINInteractionFlow);
        $interaction->displayText60 = $displayText60;
        return $interaction;
    }

    private static function ofTypeVerificationCodeChoice(string $displayText60)
    {
        $interaction = new Interaction(self::$verificationCodeChoiceInteractionFlow);
        $interaction->displayText60 = $displayText60;
        return $interaction;
    }

    private static function ofTypeConfirmationMessage(string $displayText200)
    {
        $interaction = new Interaction(self::$confirmationMessageInteractionFlow);
        $interaction->displayText200 = $displayText200;
        return $interaction;
    }

    private static function ofTypeConfirmationMessageAndVerificationCodeChoice(string $displayText200)
    {
        $interaction = new Interaction(self::$confirmationMessageAndVerificationCodeChoiceInteractionFlow);
        $interaction->displayText200 = $displayText200;
        return $interaction;
    }

}

