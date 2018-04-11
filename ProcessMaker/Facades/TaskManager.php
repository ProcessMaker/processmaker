<?php

namespace ProcessMaker\Facades;

use Illuminate\Support\Facades\Facade;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Task;

/**
 * Facade for our Task Manager
 *
 * @package ProcessMaker\Facades
 * @see \ProcessMaker\Managers\TaskManager
 *
 * @method array loadAssignees(Task $task, array $options)
 * @method array saveAssignee(Process $process, Task $task , array $options)
 *
 */
class TaskManager extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'task.manager';
    }
}
