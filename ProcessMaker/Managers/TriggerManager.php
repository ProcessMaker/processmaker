<?php

namespace ProcessMaker\Managers;

use Illuminate\Pagination\LengthAwarePaginator;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Trigger;

class TriggerManager
{
    /**
     * Get a list of All triggers in a process.
     *
     * @param Process $process
     * @param array $options
     *
     * @return LengthAwarePaginator
     */
    public function index(Process $process, array $options): LengthAwarePaginator
    {
        $start = $options['current_page'];
        $query = Trigger::where('process_id', $process->id);
        $filter = $options['filter'];
        if (!empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(function ($query) use ($filter) {
                $query->Where('title', 'like', $filter)
                    ->orWhere('description', 'like', $filter)
                    ->orWhere('type', 'like', $filter);
            });
        }
        return $query->orderBy($options['sort_by'], $options['sort_order'])
            ->paginate($options['per_page'])
            ->appends($options);
    }

    /**
     * Create a new trigger in a process.
     *
     * @param Process $process
     * @param array $data
     *
     * @return Trigger
     * @throws \Throwable
     */
    public function save(Process $process, $data): Trigger
    {
        $data['process_id'] = $process->id;

        $trigger = new Trigger();
        $trigger->fill($data);
        $trigger->saveOrFail();

        return $trigger;
    }

    /**
     * Update trigger in a process.
     *
     * @param Process $process
     * @param Trigger $trigger
     * @param array $data
     *
     * @return Trigger
     * @throws \Throwable
     */
    public function update(Process $process, Trigger $trigger, $data): Trigger
    {
        $data['process_id'] = $process->id;
        $trigger->fill($data);
        $trigger->saveOrFail();
        return $trigger;
    }

    /**
     * Remove trigger in a process.
     *
     * @param Trigger $trigger
     *
     * @return bool|null
     * @throws \Exception
     */
    public function remove(Trigger $trigger): ?bool
    {
        return $trigger->delete();
    }

}
