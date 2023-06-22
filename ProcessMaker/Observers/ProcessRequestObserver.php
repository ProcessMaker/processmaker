<?php

namespace ProcessMaker\Observers;

use ProcessMaker\Events\RequestAction;
use ProcessMaker\Events\RequestError;
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
        // Delete all tokens for subprocesses
        $childRequests = ProcessRequest::where('parent_request_id', $request->id)->get();
        foreach ($childRequests as $child) {
            $child->delete();
        }
        //A request delete child requests in cascade
        ProcessRequest::where('parent_request_id', $request->id)
            ->delete();
    }

    /**
     * Handle the process request "saved" event.
     *
     * @param  ProcessRequest  $request
     * @return void
     */
    public function saved(ProcessRequest $request)
    {
        if ($request->status === 'ERROR') {
            foreach ($request->getAttribute('errors') as $error) {
                event(new RequestError($request, $error['message']));
            }
        }
        if ($request->status === 'COMPLETED') {
            event(new RequestAction($request, RequestAction::ACTION_COMPLETED));
            // Remove scheduled tasks for this request
            $request->scheduledTasks()->delete();
        }
    }
}
