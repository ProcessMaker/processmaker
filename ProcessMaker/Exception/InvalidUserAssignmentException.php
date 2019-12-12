<?php
namespace ProcessMaker\Exception;

use Exception;

class InvalidUserAssignmentException extends Exception
{

    /**
     * @param string $variable
     * @param string $value
     */
    public function __construct($variable, $value)
    {
        parent::__construct(
            __(
                'exceptions.InvalidUserAssignmentException', [
                    'variable' => $variable,
                    'value' => $value
                ]
            )
        );
    }
}
