<?php

namespace ProcessMaker\Exception;

use Exception;

class InvalidDockerImageException extends Exception
{
    public function __construct($message)
    {
        parent::__construct($message);
    }
}
