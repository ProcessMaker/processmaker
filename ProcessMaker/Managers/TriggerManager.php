<?php

namespace ProcessMaker\Managers;

use Illuminate\Pagination\Paginator;
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
        $trigger->TRI_TITLE = isset($data['TRI_TITLE']) ? $data['TRI_TITLE'] : $trigger->TRI_TITLE;
        $trigger->TRI_TITLE = $trigger->TRI_TITLE . ',TRI_ID,' . $trigger->TRI_ID;
        $trigger->TRI_DESCRIPTION = isset($data['TRI_DESCRIPTION']) ? $data['TRI_DESCRIPTION'] : $trigger->TRI_DESCRIPTION;
        $trigger->TRI_WEBBOT = isset($data['TRI_WEBBOT']) ? $data['TRI_WEBBOT'] : $trigger->TRI_WEBBOT;
        $trigger->TRI_PARAM = isset($data['TRI_PARAM']) ? $data['TRI_PARAM'] : $trigger->TRI_PARAM;

        $trigger->saveOrFail();
        return $trigger;
    }

    /**
     * Remove trigger in a project.
     *
     * @param Process $process
     * @param Trigger $trigger
     *
     * @throws TriggerException
     */
    public function remove(Process $process, Trigger $trigger): void
    {
        $response = $trigger->delete();

        if (!$response) {
            Throw new TriggerException(__('This row does not exist!'));
        }
    }

}
