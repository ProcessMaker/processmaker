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
     * If the process does not have assigned a Manager, it returns null instead of throwing an exception.
     *
     * @param ActivityInterface $task
     * @param TokenInterface $token
     * @param Process $process
     * @param ProcessRequest $request
     * @return int|null
     */
    public function getNextUser(ActivityInterface $task, TokenInterface $token, Process $process, ProcessRequest $request)
    {
        // review for multiple managers
        $managers = $request->processVersion->manager_id;

        // Normalize: treat empty array as null
        if (is_array($managers) && empty($managers)) {
            $managers = null;
        }

        $user_id = $this->getNextManagerAssigned($managers, $task, $request);

        // Return null instead of throwing exception when no manager is found
        // This allows the process to continue without crashing
        return $user_id;
    }

    /**
     * Get the round robin manager using a true round robin algorithm
     *
     * @param array|int|null $managers Manager ID(s) - can be array, single int, or null
     * @param ActivityInterface $task
     * @param ProcessRequest $request
     * @return int|null
     */
    private function getNextManagerAssigned($managers, $task, $request)
    {
        // Handle null case
        if (is_null($managers)) {
            return null;
        }

        // Convert single value to array for backward compatibility
        if (!is_array($managers)) {
            // If it's a valid integer, convert to array
            if (is_numeric($managers) && $managers > 0) {
                $managers = [(int) $managers];
            } else {
                // Invalid single value (0, empty string, 'undefined', etc.)
                return null;
            }
        }

        // Validate array is not empty
        if (empty($managers)) {
            return null;
        }

        // Filter out invalid values (null, 0, empty strings, 'undefined', false, etc.)
        $managers = array_filter($managers, function ($id) {
            // Only accept positive integers
            if (!is_numeric($id)) {
                return false;
            }
            $id = (int) $id;

            return $id > 0;
        });

        // Re-index array after filtering
        $managers = array_values($managers);

        // Check if we have any valid managers after filtering
        if (empty($managers)) {
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
