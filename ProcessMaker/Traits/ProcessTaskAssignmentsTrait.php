<?php

namespace ProcessMaker\Traits;

use DOMElement;
use ProcessMaker\Exception\TaskDoesNotHaveUsersException;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\User;
use ProcessMaker\Models\Group;
use ProcessMaker\Providers\WorkflowServiceProvider as PM;

/**
 * Update the task assignments
 *
 * @package ProcessMaker\Traits
 */
trait ProcessTaskAssignmentsTrait
{

    /**
     * Create the process task assignments.
     */
    public static function bootProcessTaskAssignmentsTrait()
    {
        static::saving([static::class, 'updateTaskAssignments']);
    }

    public static function updateTaskAssignments(Process $process)
    {
        if (!$process->exists) {
            return;
        }
        $process->assignments()->delete();
        $definitions = $process->getDefinitions(true);
        if ($definitions) {
            $assignments = [];
            foreach ($definitions->getElementsByTagName('task') as $node) {
                $assignments = static::setAssignments($node, $assignments);
            }
            foreach ($definitions->getElementsByTagName('userTask') as $node) {
                $assignments = static::setAssignments($node, $assignments);
            }
            foreach ($definitions->getElementsByTagName('manualTask') as $node) {
                $assignments = static::setAssignments($node, $assignments);
            }
            $process->assignments()->createMany($assignments);
        }
    }

    /**
     * Populates the assignments array.
     *
     * @param DOMElement $node
     * @param array $assignments
     *
     * @return array
     */
    private static function setAssignments(DOMElement $node, array $assignments)
    {
        $assignment = $node->getAttributeNS(PM::PROCESS_MAKER_NS, 'assignment');
        if ($assignment === 'user') {
            $users = explode(',',
                $node->getAttributeNS(PM::PROCESS_MAKER_NS, 'assignedUsers'));
            if (empty($users[0])) {
                throw new TaskDoesNotHaveUsersException($node->getAttribute('id'));
            }
            $assignments[] = [
                'process_task_id' => $node->getAttribute('id'),
                'assignment_id' => $users[0],
                'assignment_type' => User::class,
            ];

        } elseif ($assignment === 'group') {
            $group = $node->getAttributeNS(PM::PROCESS_MAKER_NS, 'assignedGroups');
            if (empty($group)) {
                throw new TaskDoesNotHaveUsersException($node->getAttribute('id'));
            }
            $assignments[] = [
                'process_task_id' => $node->getAttribute('id'),
                'assignment_id' => $group,
                'assignment_type' => Group::class,
            ];
        }
        return $assignments;
    }
}
