<?php

namespace ProcessMaker\Jobs;

use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;

class CompleteActivity extends TokenAction
{
    /**
     * Execute the job.
     *
     * @return void
     */
    public function action(TokenInterface $token, ActivityInterface $activity, array $data)
    {
        //@todo requires a class to manage the data access and control the updates
        $token->getInstance()->getDataStore()->setData($data);

        $activity->complete($token);
    }
}
