<?php

namespace ProcessMaker\Contracts;

use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;

interface AssignmentRuleInterface
{

    /**
     * Return the user id to which a task must be assigned.
     *
     * @param ActivityInterface $activity
     * @param TokenInterface $token
     * @param Process $process
     * @param ProcessRequest $request
     *
     * @return string User id
     */
    public function getNextUser(ActivityInterface $activity, TokenInterface $token, Process $process, ProcessRequest $request);
}
