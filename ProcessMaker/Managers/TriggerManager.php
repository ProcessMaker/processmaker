<?php

namespace ProcessMaker\Managers;

<<<<<<< 4b1f56270dcd3f2a15143296099bffa1adc6f80a
use Illuminate\Pagination\LengthAwarePaginator;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Trigger;
=======
use ProcessMaker\Exception\TriggerException;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Triggers;
use Ramsey\Uuid\Uuid;
>>>>>>> Add method save trigger

class TriggerManager
{
    /**
     * Get a list of All triggers in a project.
     *
     * @param Process $process
     *
<<<<<<< 4b1f56270dcd3f2a15143296099bffa1adc6f80a
     * @return LengthAwarePaginator
     */
    public function index(Process $process): LengthAwarePaginator
    {
        return Trigger::where('process_id', $process->id)->paginate(20);
=======
     * @return Paginator | LengthAwarePaginator
     */
    public function loadAssignees(Process $process)
    {

>>>>>>> Add method save trigger
    }

    /**
     * Create a new trigger in a project.
     *
     * @param Process $process
<<<<<<< 4b1f56270dcd3f2a15143296099bffa1adc6f80a
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
=======
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
>>>>>>> Add method save trigger
