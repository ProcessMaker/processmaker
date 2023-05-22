<?php

namespace ProcessMaker\Exception;

use Exception;

/**
 * The the process was started by an anonymous user so it does
 * not have a requester
 */
class TaskDoesNotHaveRequesterException extends Exception
{
    /**
     * @param string $task
     */
    public function __construct()
    {
        parent::__construct(__('This process was started by an anonymous user so this task can not be assigned to the requester'));
    }
}
