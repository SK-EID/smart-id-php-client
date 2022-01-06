<?php

namespace Sk\SmartId\Exception;

/**
 * Subclasses of this exception are situation where user's action triggered ending session.
 * General practise is to ask the user to try again.
 */
abstract class UserActionException extends SmartIdException
{

}