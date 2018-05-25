<?php

namespace ProcessMaker\Facades;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Facades\Facade;
use ProcessMaker\Model\Delegation;
use ProcessMaker\Model\Task;

/**
 * Facade for our Task Delegation Manager
 *
 * @package ProcessMaker\Facades
 * @see \ProcessMaker\Managers\TasksDelegationManager
 *
 * @method static Delegation show(Task $task)
 * @method static Paginator index(array $options)
 *
 */
class TasksDelegationManager extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'task_delegation.manager';
    }
}
