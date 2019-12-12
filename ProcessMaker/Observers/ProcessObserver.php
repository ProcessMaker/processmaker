<?php

namespace ProcessMaker\Observers;

use ProcessMaker\Exception\ReferentialIntegrityException;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;

class ProcessObserver
{
    /**
     * Handle the Process "deleting" event.
     *
     * @param Process $process
     * @throws ReferentialIntegrityException
     */
    public function deleting(Process $process)
    {
        //A process can not be deleted if it has requests
        $query = ProcessRequest::where('process_id', $process->id);
        $count = $query->count();
        if ($count > 0) {
            throw new ReferentialIntegrityException($process, $query->first());
        }
    }

    /**
     * Handle the Process "saving" event.
     *
     * @param Process $process
     */
    public function saving(Process $process)
    {
        $process->start_events = $process->getUpdatedStartEvents();
        $process->self_service_tasks = $process->getUpdatedSelfServiceTasks();
    }
}
