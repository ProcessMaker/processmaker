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
                'The variable, :variable, which equals ":value", is not a valid User ID in the system', [
                    'variable' => $variable,
                    'value' => $value
                ]
            )
        );
    }
}
