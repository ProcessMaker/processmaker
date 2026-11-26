<?php

namespace ProcessMaker\Exception;

use RuntimeException;

/**
 * Thrown when the Process model cannot be resolved for ABE recipient lookups.
 */
class UnableToResolveAbeProcessModelException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Unable to resolve Process model for ABE recipients');
    }
}
