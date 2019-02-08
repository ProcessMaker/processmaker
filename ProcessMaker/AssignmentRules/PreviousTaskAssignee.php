<?php

namespace ProcessMaker\AssignmentRules;

use ProcessMaker\Contracts\AssignmentRuleInterface;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;

/**
 * Before a task is assigned, search the tokens table for a previously assigned
 * task and use that users id for the new assignment.
 *
 */
class PreviousTaskAssignee implements AssignmentRuleInterface
{

    /**
     * Before a task is assigned, search the tokens table for a previously
     * assigned task and use that users id for the new assignment.
     *
     * @param ActivityInterface $task
     * @param TokenInterface $token
     * @param Process $process
     * @param ProcessRequest $request
     * @return type
     */
    public function getNextUser(ActivityInterface $task, TokenInterface $token, Process $process, ProcessRequest $request)
    {
        $previous = $request->tokens()
            ->where('element_type', 'task')
            ->orderBy('id', 'desc')->first();
        return $previous->user_id;
    }
}
