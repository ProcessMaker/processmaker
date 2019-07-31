<?php

namespace ProcessMaker\Observers;

use ProcessMaker\Models\ProcessRequestToken;

class ProcessRequestTokenObserver
{
    /**
     * Handle the process request "saved" event.
     *
     * @param  ProcessRequest  $request
     * @return void
     */
    public function saved(ProcessRequestToken $token)
    {
        if ($token->status === 'CLOSED') {
            // Remove scheduled tasks for this request
            $token->scheduledTasks()->delete();
        }
    }
}
