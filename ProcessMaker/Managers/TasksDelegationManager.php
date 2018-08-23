<?php

namespace ProcessMaker\Managers;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use ProcessMaker\Model\Application;
use ProcessMaker\Model\Delegation;
use ProcessMaker\Model\Task;
use ProcessMaker\Model\User;

class TasksDelegationManager
{
    /**
     * Get a list of All Output Documents in a process.
     *
     * @param array $options
     *
     * @return LengthAwarePaginator
     */
    public function index(array $options): LengthAwarePaginator
    {
        $start = $options['current_page'];
        Paginator::currentPageResolver(function () use ($start) {
            return $start;
        });
        $include = $options['include'] ? explode(',', $options['include']): [];
        $include = array_unique(array_merge(['user', 'application'], $include));
        $query = Delegation::with($include);
        //only normal type tasks are displayed
        $query->where('type', '=', 'normal');
        if (!empty($options['status'])) {
            $query = $query->where('thread_status', '=', $options['status']);
        }
        $filter = $options['filter'];
        if (!empty($filter)) {
            $filter = '%' . $filter . '%';
            $user = new User();
            $task = new Task();
            $application = new Application();
            $query = Delegation::where(function ($q) use ($filter, $user) {
                    $q->whereHas('user', function ($query) use ($filter, $user) {
                        $query->where($user->getTable() . '.firstname', 'like', $filter)
                            ->orWhere($user->getTable() . '.lastname', 'like', $filter);
                    });
                })
                ->orWhere(function ($q) use ($filter, $task) {
                    $q->whereHas('task', function ($query) use ($filter, $task) {
                        $query->where($task->getTable() . '.title', 'like', $filter)
                            ->orWhere($task->getTable() . '.description', 'like', $filter);
                    });
                })
                ->orWhere(function ($q) use ($filter, $application) {
                    $q->whereHas('application', function ($query) use ($filter, $application) {
                        $query->where($application->getTable() . '.APP_TITLE', 'like', $filter)
                            ->orWhere($application->getTable() . '.APP_DESCRIPTION', 'like', $filter);
                    });
                });

        }
        return $query->orderBy($options['sort_by'], $options['sort_order'])
            ->paginate($options['per_page'])
            ->appends($options);
    }

    /**
     * Get a task delegation
     *
     * @param Task $task
     *
     * @return Delegation
     */
    public function show(Task $task): Delegation
    {
        return Delegation::where('task_id', $task->id)
            ->with('task', 'user', 'application')
            ->first();
    }


}
