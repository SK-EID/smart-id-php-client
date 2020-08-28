<?php


namespace Sk\SmartId\Api\Data;


class SemanticsIdentifierTypes
{

    /**
     * @var string
     * "PNO" for identification based on (national) personal number (national civic registration number).
     */
    const PNO = "PNO";

    /**
     * @var string
     * "PAS" for identification based on passport number.
     */
    const PAS = "PAS";

    /**
     * @var string
     * "IDC" for identification based on national identity card number.
     */
    const IDC = "IDC";

}