<?php

namespace ProcessMaker\Exception;

use Exception;

class ExportEmptyProcessException extends Exception
{
    public function __construct(Exception $e)
    {
        parent::__construct('The process to export is empty.');
    }
}
