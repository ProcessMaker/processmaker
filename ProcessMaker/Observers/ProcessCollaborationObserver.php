<?php

namespace ProcessMaker\Observers;

use ProcessMaker\Models\ProcessCollaboration;
use ProcessMaker\Models\ProcessRequest;

class ProcessCollaborationObserver
{
    /**
     * Handle the process request "deleting" event.
     *
     * @param  ProcessCollaboration  $processCollaboration
     * @return void
     */
    public function deleting(ProcessCollaboration $processCollaboration)
    {
        //If the collaboration is deleted the request stays without collaboration
        ProcessRequest::where('process_collaboration_id', $processCollaboration->id)
            ->update(['process_collaboration_id' => null]);

    }
}
