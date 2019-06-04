<?php

namespace ProcessMaker\Observers;

use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ScheduledTask;

class ProcessRequestObserver
{
    /**
     * Handle the process request "deleting" event.
     *
     * @param  ProcessRequest  $request
     * @return void
     */
    public function deleting(ProcessRequest $request)
    {
        //A Scheduled Task delete child requests in cascade
        ScheduledTask::where('process_request_id', $request->id)
            ->delete();

        //A Scheduled Task delete child requests in cascade
        ProcessRequestToken::where('process_request_id', $request->id)
            ->delete();
        //A Process Request Token delete child requests in cascade
        ProcessRequestToken::where('subprocess_request_id', $request->id)
            ->delete();
        //A request delete child requests in cascade
        ProcessRequest::where('parent_request_id', $request->id)
            ->delete();

    }
}
