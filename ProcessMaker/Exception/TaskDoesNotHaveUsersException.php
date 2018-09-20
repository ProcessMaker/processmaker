<?php
namespace ProcessMaker\Exception;

use Exception;

/**
 * The task does not have users to assign
 *
 */
class TaskDoesNotHaveUsersException extends Exception
{

    /**
     * @param string $task
     */
    public function __construct($task)
    {
        parent::__construct(__('exceptions.TaskDoesNotHaveUsersException', ['task' => $task]));
    }
}
