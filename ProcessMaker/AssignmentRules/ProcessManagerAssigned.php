<?php

namespace ProcessMaker\AssignmentRules;

use ProcessMaker\Contracts\AssignmentRuleInterface;
use ProcessMaker\Exception\ThereIsNoProcessManagerAssignedException;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
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
     * @return int|null
     * @throws ThereIsNoProcessManagerAssignedException
     */
    public function getNextUser(ActivityInterface $task, TokenInterface $token, Process $process, ProcessRequest $request)
    {
        // review for multiple managers
        $managers = $request->processVersion->manager_id;
        $user_id = $this->getNextManagerAssigned($managers, $task, $request);
        if (!$user_id) {
            throw new ThereIsNoProcessManagerAssignedException($task);
        }

        return $user_id;
    }

    /**
     * Get the round robin manager using a true round robin algorithm
     *
     * @param array $managers
     * @param ActivityInterface $task
     * @param ProcessRequest $request
     * @return int|null
     */
    private function getNextManagerAssigned($managers, $task, $request)
    {
        // Validate input
        if (empty($managers) || !is_array($managers)) {
            return null;
        }

        // If only one manager, return it
        if (count($managers) === 1) {
            return $managers[0];
        }

        // get the last manager assigned to the task across all requests
        $last = ProcessRequestToken::where('process_id', $request->process_id)
            ->where('element_id', $task->getId())
            ->whereIn('user_id', $managers)
            ->orderBy('created_at', 'desc')
            ->first();

        $user_id = $last ? $last->user_id : null;

        sort($managers);

        $key = array_search($user_id, $managers);
        if ($key === false) {
            // If no previous manager found, start with the first manager
            $key = 0;
        } else {
            // Move to the next manager in the round-robin
            $key = ($key + 1) % count($managers);
        }
        $user_id = $managers[$key];

        return $user_id;
    }
}
