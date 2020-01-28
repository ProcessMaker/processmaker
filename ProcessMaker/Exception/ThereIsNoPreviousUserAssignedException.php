<?php

namespace ProcessMaker\Exception;

use Exception;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;

/**
 * There is no previous user assigned to assign to the task
 *
 */
class ThereIsNoPreviousUserAssignedException extends Exception
{
    /**
     * @param string $task
     */
    public function __construct(ActivityInterface $task)
    {
        parent::__construct(__('Can not assign this task because there is no previous user assigned before this task', ['task' => $task->getName()]));
    }
}
