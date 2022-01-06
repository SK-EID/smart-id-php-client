<?php

namespace Sk\SmartId\Exception;

/**
 * Exceptions that subclass this mark situations where something is wrong with
 * client-side integration or how Relying Party account has been configured by Smart-ID operator
 * or Smart-ID server is under maintenance.
 * With these types of errors there is not recommended to ask the user for immediate retry.
 */
abstract class EnduringSmartIdException extends SmartIdException
{

}