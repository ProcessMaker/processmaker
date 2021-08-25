<?php

namespace ProcessMaker\AssignmentRules;

use ProcessMaker\Contracts\AssignmentRuleInterface;
use ProcessMaker\Exception\ThereIsNoPreviousUserAssignedException;
use ProcessMaker\Exception\ThereIsNoProcessManagerAssignedException;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;

/**
 * Before a task is assigned, search the tokens table for a previously assigned
 * task and use that users id for the new assignment.
 *
 */
class ProcessManagerAssigned implements AssignmentRuleInterface
{

    /**
     * Before a task is assigned, search the tokens table for a previously
     * assigned task and use that users id for the new assignment.
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
        if (!$user_id) {
            throw new ThereIsNoProcessManagerAssignedException($task);
        }
        return $user_id;
    }
}
