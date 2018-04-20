<?php

namespace ProcessMaker\Managers;

use ProcessMaker\Exception\TriggerException;
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
     * @return Paginator | LengthAwarePaginator
     */
    public function loadAssignees(Process $process)
    {

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

}