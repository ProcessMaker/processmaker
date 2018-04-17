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
    const ASSIGNEE_NORMAL = 1;
    const ASSIGNEE_ADHOC = 2;
    /**
     * List the users and groups assigned to a task.
     *
     * @param Task $activity
     * @param array $options
     *
     * @return array
     */
    public function loadAssignees(Task $activity, array $options)
    {
        $assignee = Task::where('TAS_UID', $activity->TAS_UID)->first();
        $response = [];
        if ($assignee) {
            $users = $assignee->usersAssigned()->get()->paginate($options['limit']);
        }
        $groupAssignees = TaskUser::where('TAS_UID', $activity->TAS_UID)->onlyGroups()->with('group')->get();
        return [];
    }

    /**
     * List the users and groups available to a task.
     *
     * @param Process $process
     * @param Task $activity
     * @param array $options
     *
     * @return array
     */
    public function loadAvailable(Process $process, Task $activity, array $options)
    {
        return [];
    }

    /**
     * Save the assignment of the user or group in a task.
     *
     * @param Process $process
     * @param Task $activity
     * @param array $options
     *
     * @return array
     *
     * @throws TaskAssignedException
     * @throws \Throwable
     */
    public function saveAssignee(Process $process, Task $activity, array $options): array
    {
        //todo validate Process and task
        $query = TaskUser::where('TAS_UID', $activity->TAS_UID)->type(self::ASSIGNEE_NORMAL);
        $type = User::TYPE;
        switch (strtoupper($options['aas_type'])) {
            case 'USER':
                $check = User::where('USR_UID', $options['aas_uid'])->get()->toArray();
                $field = 'USR_ID';
                $query->onlyUsers();
                break;
            case 'GROUP':
                $check = Group::where('GRP_UID', $options['aas_uid'])->get()->toArray();
                $field = 'GRP_ID';
                $query->onlyGroups();
                $type = Group::TYPE;
                break;
            default:
                $check = [];
                break;
        }
        if (!$check) {
            throw new TaskAssignedException(__('This id :uid does not correspond to a registered :type', ['uid' => $options['aas_uid'], 'type' => $options['aas_type']]));
        }
        $exist = $query->where('USR_UID', $options['aas_uid'])->get()->toArray();
        if ($exist) {
            throw new TaskAssignedException(__('This ID: :user is already assigned to task: :task', ['user' => $options['aas_uid'], 'task' => $activity->TAS_UID]));
        }
        $assigned = new TaskUser();
        $assigned->TAS_UID = $activity->TAS_UID;
        $assigned->TAS_ID = $activity->TAS_ID;
        $assigned->USR_UID = $options['aas_uid'];
        $assigned->USR_ID = $check[0][$field];
        $assigned->TU_RELATION = $type;
        $assigned->TU_TYPE = 1;
        $assigned->saveOrFail();

        return $assigned->toArray();
    }

    /**
     * Remove user or group assigned to Activity
     *
     * @param Process $process
     * @param Task $activity
     * @param string $assignee
     *
     * @return void
     * @throws TaskAssignedException
     */
    public function removeAssignee(Process $process, Task $activity, $assignee): void
    {
        $response = TaskUser::where('TAS_UID', $activity->TAS_UID)
            ->where('USR_UID', $assignee)
            ->type(self::ASSIGNEE_NORMAL)
            ->delete();

        if (!$response) {
            Throw new TaskAssignedException(__('This row does not exist!'));
        }
    }

}