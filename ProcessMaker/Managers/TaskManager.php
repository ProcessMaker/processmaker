<?php

namespace ProcessMaker\Managers;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
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

    /**
     * Get a single user or group assigned to a task.
     *
     * @param Process $process
     * @param Task $activity
     * @param string $assignee
     *
     * @return TaskUser
     * @throws TaskAssignedException
     */
    public function getInformationAssignee(Process $process, Task $activity, $assignee): TaskUser
    {
        $user = new User();
        $information = $activity->usersAssigned()->where($user->getTable() . '.USR_UID', $assignee)->get();
        if (!$information->isEmpty()) {
            return $this->formatDataAssignee($information->first()->toArray(), $user::TYPE);
        }
        $group = new Group();
        $information = $activity->groupsAssigned()->where($group->getTable() . '.GRP_UID', $assignee)->get();
        if (!$information->isEmpty()) {
            $information = $information->first();
            $name = $information->GRP_TITLE . ' ' . $group::STATUS_INACTIVE;
            if ($information->GRP_STATUS !== $group::STATUS_INACTIVE) {
                $name = $information->GRP_TITLE . ' (' . $information->users()->count() . ') ';
            }
            $information->GRP_TITLE = $name;
            return $this->formatDataAssignee($information->first()->toArray(), $group::TYPE);
        }

        Throw new TaskAssignedException(__('Record not found for id: :assignee', ['assignee' => $assignee]));
    }

    /**
     *  Return a list of assignees of an activity
     *
     * @param Process $process
     * @param Task $activity
     * @param array $options
     *
     * @return LengthAwarePaginator
     */
    public function getInformationAllAssignee(Process $process, Task $activity, $options): LengthAwarePaginator
    {
        $query = $activity->usersAssigned();
        if (!empty($options['filter'])) {
            $user = new User();
            $query->where($user->getTable() . '.USR_FIRSTNAME', 'like', '%' . $options['filter'] . '%')
                ->orWhere($user->getTable() . '.USR_LASTNAME', 'like', '%' . $options['filter'] . '%');
        }
        $users = $query->get();
        $information = [];
        foreach ($users as $user) {
            $information[] = $this->formatDataAssignee($user->toArray(), User::TYPE)->toArray();
        }

        $query = $activity->groupsAssigned();
        if (!empty($options['filter'])) {
            $group = new Group();
            $query->where($group->getTable() . '.GRP_TITLE', 'like', '%' . $options['filter'] . '%');
        }
        $groups = $query->get();
        foreach ($groups as $group) {
            $name = $group->GRP_TITLE . ' ' . $group::STATUS_INACTIVE;
            if ($group->GRP_STATUS !== $group::STATUS_INACTIVE) {
                $name = $group->GRP_TITLE . ' (' . $group->users()->count() . ') ';
            }
            $group->GRP_TITLE = $name;
            $information[] = $this->formatDataAssignee($group->toArray(), Group::TYPE)->toArray();
        }

        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $collection = new Collection($information);
        $currentPageResults = $collection->slice(($currentPage - 1) * $options['limit'], $options['limit'])->all();
        return new LengthAwarePaginator($currentPageResults, count($collection), $options['limit']);
    }

    /**
     * Format data response
     *
     * @param array $data
     * @param string $type
     *
     * @return TaskUser
     */
    private function formatDataAssignee($data, $type): TaskUser
    {
        $assigned = new TaskUser();
        $assigned->aas_uid = '';
        $assigned->aas_name = '';
        $assigned->aas_lastname = '';
        $assigned->aas_username = '';
        switch ($type) {
            case User::TYPE:
                $assigned->aas_uid = $data['USR_UID'];
                $assigned->aas_name = $data['USR_FIRSTNAME'];
                $assigned->aas_lastname = $data['USR_LASTNAME'];
                $assigned->aas_username = $data['USR_USERNAME'];
                break;
            case Group::TYPE:
                $assigned->aas_uid = $data['GRP_UID'];
                $assigned->aas_name = $data['GRP_TITLE'];
                $assigned->aas_username = $data['GRP_TITLE'];
                break;
        }
        $assigned->aas_type = strtolower($type);

        return $assigned;
    }


}