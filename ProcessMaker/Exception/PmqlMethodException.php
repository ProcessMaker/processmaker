<?php
namespace ProcessMaker\Exception;

use Exception;

class PmqlMethodException extends Exception
{
    private $field;
    
    /**
     * @param string $message
     */
    public function __construct($field, $message)
    {
        $this->field = $field;
        return parent::__construct(__($message));
    }

    public function getField()
    {
        return $this->field;
    }
}
