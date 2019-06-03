<?php

namespace ProcessMaker\Observers;

use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ScheduledTask;

class ProcessRequestObserver
{
    /**
     * Handle the process request "deleted" event.
     *
     * @param  ProcessRequest  $request
     * @return void
     */
    public function deleted(ProcessRequest $request)
    {
        ScheduledTask::where('process_request_id', $request->id)
            ->delete();

        ProcessRequestToken::where('process_request_id', $request->id)
            ->delete();

        ProcessRequestToken::where('subprocess_request_id', $request->id)
            ->delete();

        ProcessRequest::where('parent_request_id', $request->id)
            ->delete();

    }
}
