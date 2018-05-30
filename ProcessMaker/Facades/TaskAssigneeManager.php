<?php

namespace ProcessMaker\Facades;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Facades\Facade;
use ProcessMaker\Model\Task;
use ProcessMaker\Model\TaskUser;

/**
 * Facade for our Task Assignee Manager
 *
 * @package ProcessMaker\Facades
 * @see \ProcessMaker\Managers\TaskAssigneeManager
 *
 * @method static array saveAssignee(Task $task , array $options)
 * @method static Paginator|LengthAwarePaginator loadAssignees(Task $task, array $options, boolean $paged = false)
 * @method static Paginator|LengthAwarePaginator loadAvailable(Task $activity, array $options, $paged = false)
 * @method static void removeAssignee(Task $activity, string $assignee)
 * @method static TaskUser getInformationAssignee(Task $activity, string $assignee)
 * @method static Paginator getInformationAllAssignee(Task $activity, array $options)
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
