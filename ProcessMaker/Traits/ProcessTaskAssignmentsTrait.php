<?php

namespace ProcessMaker\Traits;

use DOMElement;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\User;
use ProcessMaker\Providers\WorkflowServiceProvider as PM;

/**
 * Trait that allows that all dates of an Eloquent model be serialized in ISO 8601 format
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
        static::saved([static::class, 'updateTaskAssignments']);
    }

    public static function updateTaskAssignments(Process $process)
    {
        $process->assignments()->delete();
        $definitions = $process->getDefinitions();
        if ($definitions) {
            $assignments = [];
            foreach ($definitions->getElementsByTagName('task') as $node) {
                $assignments = static::setAssignments($node, $assignments);
            }
            foreach ($definitions->getElementsByTagName('userTask') as $node) {
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
        $users = explode(',',
            $node->getAttributeNS(PM::PROCESS_MAKER_NS, 'assignedUsers'));
        if ($assignment === 'user') {
            $assignments[] = [
                'process_task_id' => $node->getAttribute('id'),
                'assignment_id' => $users[0],
                'assignment_type' => User::class,
            ];
        }
        return $assignments;
    }
}
