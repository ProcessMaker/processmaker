<?php

namespace ProcessMaker\Facades;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Facade;
use ProcessMaker\Model\Task;
use ProcessMaker\Model\Process;

/**
 * Facade for our OutPut Document Manager
 *
 * @package ProcessMaker\Facades
 * @see \ProcessMaker\Managers\TaskAssigneeManager
 *
 * @method static Paginator index(Process $process, array $options)
 * @method static Task save(Process $process, array $data)
 * @method static array update(Process $process, Task $task, array $data)
 * @method static boolean|null remove(Task $task)
 *
 */
class TaskAssigneeManager extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'task_assignee.manager';
    }
}