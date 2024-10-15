<?php

namespace ProcessMaker\Exception;

use Exception;

class MissingScreenPageException extends Exception
{
    protected $message = 'The specified screen page does not exist in the configuration. Please try saving the screen or check the configuration.';
}
