<?php

namespace ProcessMaker\Jobs;

use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;
use Illuminate\Contracts\Queue\ShouldQueue;

class CompleteActivity extends TokenAction implements ShouldQueue
{

    /**
     * Execute the job.
     *
     * @return void
     */
    public function action(TokenInterface $token, ActivityInterface $activity, array $data)
    {
        $dataStore = $token->getInstance()->getDataStore();
        //@todo requires a class to manage the data access and control the updates
        foreach ($data as $key => $value) {
            $dataStore->putData($key, $value);
        }

        $activity->complete($token);
    }
}
