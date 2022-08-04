<?php

namespace ProcessMaker\AssignmentRules;

use ProcessMaker\Contracts\AssignmentRuleInterface;
use ProcessMaker\Exception\ThereIsNoProcessManagerAssignedException;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;

/**
 * The task is assigned to the Manager of the Process.
 */
class ProcessManagerAssigned implements AssignmentRuleInterface
{
    /**
     * The task is assigned to the Manager of the Process.
     *
     * It takes in count the process version of the request.
     * If the process does not have assigned a Manager, it throws an exception.
     *
     * @param ActivityInterface $task
     * @param TokenInterface $token
     * @param Process $process
     * @param ProcessRequest $request
     * @return int
     * @throws ThereIsNoProcessManagerAssignedException
     */
    public function getNextUser(ActivityInterface $task, TokenInterface $token, Process $process, ProcessRequest $request)
    {
        $user_id = $request->processVersion->manager_id;
        if (! $user_id) {
            throw new ThereIsNoProcessManagerAssignedException($task);
        }

        return $user_id;
    }
}
