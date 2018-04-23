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
        return Trigger::where('PRO_UID', $process->PRO_UID)->simplePaginate(20);
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
        $data['TRI_UID'] = str_replace('-', '', Uuid::uuid4());
        $data['PRO_UID'] = $process->PRO_UID;
        $data['PRO_ID'] = $process->PRO_ID;

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
        $data['PRO_UID'] = $process->PRO_UID;
        $data['PRO_ID'] = $process->PRO_ID;
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
