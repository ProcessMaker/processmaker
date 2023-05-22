<?php

namespace ProcessMaker\Exception;

use Exception;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;

/**
 * There is no previous user assigned to assign to the task
 */
class ThereIsNoProcessManagerAssignedException extends Exception
{
    /**
     * @param string $task
     */
    public function __construct(ActivityInterface $task)
    {
        parent::__construct(__('Task cannot be assigned since there is no Process Manager associated to the process.', ['task' => $task->getName()]));
    }
}
