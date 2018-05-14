<?php

namespace ProcessMaker\Managers;

use Illuminate\Pagination\Paginator;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Trigger;
use Ramsey\Uuid\Uuid;

class TriggerManager
{
    /**
     * Get a list of All triggers in a project.
     *
     * @param Process $process
     *
     * @return Paginator
     */
    public function index(Process $process): Paginator
    {
        return Trigger::where('process_id', $process->id)->simplePaginate(20);
    }

    /**
     * Create a new trigger in a project.
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
     * Update trigger in a project.
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
     * Remove trigger in a project.
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
