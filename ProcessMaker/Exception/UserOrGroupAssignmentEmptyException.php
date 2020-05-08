<?php
namespace ProcessMaker\Exception;

use Exception;

/**
 * The task does not have users to assign
 *
 */
class UserOrGroupAssignmentEmptyException extends Exception
{

    /**
     * @param string $task
     */
    public function __construct($task)
    {
        parent::__construct(__('The task ":task" has an incomplete assignment. The group was not found or it does not have users.', ['task' => $task]));
    }
}
