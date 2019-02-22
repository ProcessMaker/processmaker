<?php

namespace ProcessMaker\Traits;

use DOMElement;
use ProcessMaker\Exception\TaskDoesNotHaveUsersException;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\User;
use ProcessMaker\Models\Group;
use ProcessMaker\Providers\WorkflowServiceProvider as PM;

/**
 * Update the start event assignments/permissions
 *
 * @package ProcessMaker\Traits
 */
trait ProcessStartEventAssignmentsTrait
{

    /**
     * Boot start event assignments/permissions event handling.
     */
    public static function bootProcessStartEventAssignmentsTrait()
    {
        static::saved([static::class, 'updateStartEventAssignments']);
    }

    public static function updateStartEventAssignments(Process $process)
    {
        if (!$process->exists) {
            return;
        }
        $definitions = $process->getDefinitions(true);
        if ($definitions) {
            foreach ($definitions->getElementsByTagName('startEvent') as $node) {
                \Illuminate\Support\Facades\Log::info('Llego a: ' . $node->getAttribute('id'));
                static::setStartEventPermission($process, $node);
            }
        }
    }

    /**
     * Populates the assignments array.
     *
     * @param DOMElement $node
     *
     * @return array
     */
    private static function setStartEventPermission(Process $process, DOMElement $node)
    {
        $nodeId = $node->getAttribute('id');
        $assignedUsers = $node->getAttributeNS(PM::PROCESS_MAKER_NS, 'assignedUsers');
        $assignedGroups = $node->getAttributeNS(PM::PROCESS_MAKER_NS, 'assignedGroups');
        $users = $assignedUsers ? explode(',', $assignedUsers) : [];
        $groups = $assignedGroups ? explode(',', $assignedGroups) : [];
        $startUsers = [];
        foreach ($users as $item) {
            $startUsers[$item] = ['method' => 'START', 'node' => $nodeId];
        }
        
        $startGroups = [];
        foreach ($groups as $item) {
            $startGroups[$item] = ['method' => 'START', 'node' => $nodeId];
        }

        //Syncing users and groups that can start this process
        $process->usersCanStart($nodeId)->sync($startUsers);
        $process->groupsCanStart($nodeId)->sync($startGroups); 

    }
}
