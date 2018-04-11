<?php

namespace ProcessMaker\Managers;

use ProcessMaker\Exception\TaskAssignedException;
use ProcessMaker\Model\Group;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Task;
use ProcessMaker\Model\TaskUser;
use ProcessMaker\Model\User;

class TaskManager
{
    /**
     * List the users and groups assigned to a task.
     *
     * @param Task $task
     * @param array $options
     *
     * @return array
     */
    public function loadAssignees(Task $task, array $options)
    {
        $userAssignees = TaskUser::where('TAS_UID', $task->TAS_UID)->onlyUsers()->with('user')->get();
        $groupAssignees = TaskUser::where('TAS_UID', $task->TAS_UID)->onlyGroups()->with('group')->get();
        return [];
    }

    /**
     * List the users and groups available to a task.
     *
     * @param Process $process
     * @param Task $task
     * @param array $options
     *
     * @return array
     */
    public function loadAvailable(Process $process, Task $task, array $options)
    {
        return [];
    }

    /**
     * Save the assignment of the user or group in a task.
     *
     * @param Process $process
     * @param Task $task
     * @param array $options
     *
     * @return array
     */
    public function saveAssignee(Process $process, Task $task, array $options)
    {
        try
        {
            //todo validate Process and task
            switch (strtoupper($options['aas_type'])) {
                case 'USER':
                    $check = User::where('USR_UID', $options['ass_uid'])->get()->toArray();
                    break;
                case 'GROUP':
                    $check = Group::where('GRP_UID', $options['ass_uid'])->get()->toArray();
                    break;
                default:
                    $check = [];
                    break;
            }
            if (!$check) {
                throw new TaskAssignedException(__('This id: {uid} does not correspond to a registered {type}', ['uid' => $options['ass_uid'], 'type' => $options['aas_type']]));
            }

        } catch (TaskAssignedException $exception) {
            throw $exception;
        }
    }

}