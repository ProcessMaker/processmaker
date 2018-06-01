<?php

namespace ProcessMaker\Managers;

use Illuminate\Pagination\LengthAwarePaginator;
use ProcessMaker\Model\Task;
use ProcessMaker\Model\Process;

class TaskAssigneeManager
{

    /**
     * Get a list of All Task in a process.
     *
     * @param Process $process
     * @param array $options
     *
     * @return LengthAwarePaginator
     */
    public function index(Process $process, array $options): LengthAwarePaginator
    {
        $query = Task::where('process_id', $process->id);
        $filter = $options['filter'];
        if (!empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(function ($query) use ($filter) {
                $query->Where('title', 'like', $filter)
                    ->orWhere('description', 'like', $filter);
            });
        }
        return $query->orderBy($options['sort_by'], $options['sort_order'])
            ->paginate($options['per_page'])
            ->appends($options);
    }

    /**
     * Create a new Task in a process.
     *
     * @param Process $process
     * @param array $data
     *
     * @return Task
     * @throws \Throwable
     */
    public function save(Process $process, $data): Task
    {
        $data['process_id'] = $process->id;

        $task = new Task();
        $task->fill($data);
        $task->saveOrFail();

        return $task->refresh();
    }

    /**
     * Update Task in a process.
     *
     * @param Process $process
     * @param Task $task
     * @param array $data
     *
     * @return Task
     * @throws \Throwable
     */
    public function update(Process $process, Task $task, $data): Task
    {
        $data['process_id'] = $process->id;
        $task->fill($data);

        $task->saveOrFail();
        return $task->refresh();
    }

    /**
     * Remove Task in a process.
     *
     * @param Task $task
     *
     * @return bool|null
     * @throws \Exception
     */
    public function remove(Task $task): ?bool
    {
        return $task->delete();
    }

}
