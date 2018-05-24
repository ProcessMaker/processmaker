<?php

namespace ProcessMaker\Managers;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use ProcessMaker\Model\Delegation;
use ProcessMaker\Model\Task;

class TasksDelegationManager
{
    /**
     * Get a list of All Output Documents in a project.
     *
     * @param Task $task
     * @param array $options
     *
     * @return LengthAwarePaginator
     */
    public function index(Task $task, array $options): LengthAwarePaginator
    {
        $start = $options['current_page'];
        Paginator::currentPageResolver(function () use ($start) {
            return $start;
        });
        $query = Delegation::where('process_id', $task->id);
        $filter = $options['filter'];
        if (!empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(function ($query) use ($filter) {
                $query->Where('title', 'like', $filter)
                    ->orWhere('description', 'like', $filter)
                    ->orWhere('filename', 'like', $filter)
                    ->orWhere('report_generator', 'like', $filter)
                    ->orWhere('type', 'like', $filter)
                    ->orWhere('versioning', 'like', $filter)
                    ->orWhere('current_revision', 'like', $filter)
                    ->orWhere('tags', 'like', $filter);
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
