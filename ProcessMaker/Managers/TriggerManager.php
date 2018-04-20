<?php

namespace ProcessMaker\Managers;

use ProcessMaker\Exception\TriggerException;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Triggers;
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
     *
     * @param $data
     *
     * @throws TriggerException
     */
    public function save(Process $process, $data)
    {
        if (empty($data['TRI_TITLE'])) {
            throw new TriggerException(__('This filed :field is required', ['field' => 'TRI_TITLE']));
        }

        $data['TRI_UID'] = str_replace('-', '', Uuid::uuid4());
        $data['PRO_UID'] = $process->PRO_UID;
        $data['PRO_ID'] = $process->PRO_ID;

        $trigger = new Triggers();
        $trigger->fill($data);
        $trigger->saveOrFail();

        return $trigger;
    }

}