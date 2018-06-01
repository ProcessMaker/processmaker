<?php

namespace ProcessMaker\Managers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use ProcessMaker\Exception\TaskAssignedException;
use ProcessMaker\Model\Group;
use ProcessMaker\Model\Task;
use ProcessMaker\Model\TaskUser;
use ProcessMaker\Model\User;

class TaskAssigneeManager
{

    /**
     * List the users and groups assigned to a task.
     *
     * @param Task $activity
     * @param array $options start|per_page|filter
     * @param boolean $paged
     *
     * @return Paginator | LengthAwarePaginator
     */
    public function loadAssignees(Task $activity, array $options, $paged = false)
    {
        $filter = $options['filter'];
        $query = TaskUser::where('task_id', $activity->id)
            ->with('user')
            ->with('group');

        if (!empty($filter)) {
            $query = $this->generateFilterUserGroup(TaskUser::where('task_id', $activity->id), $filter);
        }

        $assignees = $query->paginate($options['per_page']);
        $assignees->appends($options);

        foreach ($assignees as $assigned) {
            if (!empty($assigned->group)) {
                $assigned->group->title = $this->labelGroup($assigned->group);
            }
            $assigned = $this->formatAssigneeData($assigned);
        }

        return $assignees;
    }

    /**
     * List the users and groups available to a task.
     *
     * @param Task $activity
     * @param array $options
     * @param boolean $paged
     *
     * @return Paginator | LengthAwarePaginator
     */
    public function loadAvailable(Task $activity, array $options, $paged = false)
    {
        $query = TaskUser::where('task_id', $activity->id)
            ->with('user')
            ->with('group');
        $usersAssigned = [];
        foreach ($query->get() as $assigned) {
            $usersAssigned[] = $assigned->user_id;
        }

        $query = User::whereNotIn('id', $usersAssigned);
        if (!empty($options['filter'])) {
            $user = new User();
            $query->where($user->getTable() . '.firstname', 'like', '%' . $options['filter'] . '%')
                ->orWhere($user->getTable() . '.lastname', 'like', '%' . $options['filter'] . '%');
        }
        $information = [];
        $assignee = new TaskUser();
        foreach ($query->get() as $user) {
            $assignee->assign_uid = $user->uid;
            $assignee->assign_name = $user->firstname;
            $assignee->assign_lastname = $user->lastname;
            $assignee->assign_username = $user->username;
            $assignee->assign_type = User::TYPE;
            $information[] = $assignee;
        }

        $query = Group::whereNotIn('id', $usersAssigned);
        if (!empty($options['filter'])) {
            $group = new Group();
            $query->where($group->getTable() . '.title', 'like', '%' . $options['filter'] . '%');
        }
        foreach ($query->get() as $group) {
            $group->title = $this->labelGroup($group);
            $assignee->assign_uid = $group->uid;
            $assignee->assign_name = $group->title;
            $assignee->assign_lastname = '';
            $assignee->assign_username = $group->title;
            $assignee->assign_type = Group::TYPE;
            $information[] = $assignee;
        }

        $limit = $options['per_page'];
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $collection = new Collection($information);
        $currentPageResults = $collection->slice(($currentPage - 1) * $limit, $limit)->all();
        return new LengthAwarePaginator($currentPageResults, count($collection), $limit);
    }

    /**
     * Save the assignment of the user or group in a task.
     *
     * @param Task $activity
     * @param array $options
     *
     * @return array
     *
     * @throws TaskAssignedException
     * @throws \Throwable
     */
    public function saveAssignee(Task $activity, array $options): array
    {
        $query = TaskUser::where('task_id', $activity->id)->type(TaskUser::ASSIGNEE_NORMAL);
        $type = strtoupper($options['type']);
        switch ($type) {
            case User::TYPE:
                $groupOrUserFound = User::where('uid', $options['uid'])->first();
                $query->onlyUsers();
                break;
            case Group::TYPE:
                $groupOrUserFound = Group::where('uid', $options['uid'])->first();
                $query->onlyGroups();
                break;
            default:
                $groupOrUserFound = false;
                break;
        }
        if (!$groupOrUserFound) {
            throw new TaskAssignedException(__('This id :uid does not correspond to a registered :type', ['uid' => $options['uid'], 'type' => $options['type']]));
        }
        if ($query->where('user_id', $groupOrUserFound->id)->exists()) {
            throw new TaskAssignedException(__('This ID: :user is already assigned to task: :task', ['user' => $options['uid'], 'task' => $activity->uid]));
        }
        $assigned = new TaskUser();
        $assigned->task_id = $activity->id;
        $assigned->user_id = $groupOrUserFound->id;
        $assigned->task_users_type = $type;
        $assigned->type = TaskUser::ASSIGNEE_NORMAL;
        $assigned->saveOrFail();

        return $assigned->toArray();
    }

    /**
     * Remove user or group assigned to Activity
     *
     * @param Task $activity
     * @param string $assignee
     *
     * @return void
     * @throws TaskAssignedException
     */
    public function removeAssignee(Task $activity, $assignee): void
    {
        $user = new User();
        $group = new Group();
        $deleteResponse = TaskUser::where('task_id', $activity->id)
            ->where(function ($q) use ($assignee, $user) {
                $q->whereHas('user', function ($query) use ($assignee, $user) {
                    $query->where($user->getTable() . '.uid', '=', $assignee);
                });
            })
            ->orWhere(function ($q) use ($assignee, $group) {
                $q->whereHas('group', function ($query) use ($assignee, $group) {
                    $query->where($group->getTable() . '.uid', '=', $assignee);
                });
            })
            ->type(TaskUser::ASSIGNEE_NORMAL)
            ->delete();

        if (!$deleteResponse) {
            Throw new TaskAssignedException(__('This row does not exist!'));
        }
    }

    /**
     * Get a single user or group assigned to a task.
     *
     * @param Task $activity
     * @param string $assignee uid user
     *
     * @return TaskUser
     * @throws TaskAssignedException
     */
    public function getInformationAssignee(Task $activity, $assignee): TaskUser
    {
        $user = new User();
        $group = new Group();
        $taskAssignee = TaskUser::where('task_id', $activity->id)
            ->where(function ($q) use ($assignee, $user) {
                $q->whereHas('user', function ($query) use ($assignee, $user) {
                    $query->where($user->getTable() . '.uid', '=', $assignee);
                });
            })
            ->orWhere(function ($q) use ($assignee, $group) {
                $q->whereHas('group', function ($query) use ($assignee, $group) {
                    $query->where($group->getTable() . '.uid', '=', $assignee);
                });
            })
            ->get();

        if ($taskAssignee->isEmpty()) {
            Throw new TaskAssignedException(__('Record not found for id: :assignee', ['assignee' => $assignee]));
        }

        return $this->formatAssigneeData($taskAssignee->first());
    }

    /**
     *  Get a list all the users who are assigned to a task (including users that are within groups).
     *
     * @param Task $activity
     * @param array $options
     *
     * @return LengthAwarePaginator
     */
    public function getInformationAllAssignees(Task $activity, $options): LengthAwarePaginator
    {
        $query = TaskUser::where('task_id', $activity->id)
            ->with('user', 'group.users');
        $filter = $options['filter'];

        if (!empty($filter)) {
            $user = New User();
            $query = TaskUser::where('task_id', $activity->id)
                ->where(function ($q) use ($filter, $user) {
                    $q->whereHas('user', function ($query) use ($filter, $user) {
                        $query->where($user->getTable() . '.firstname', 'like', '%' . $filter . '%')
                            ->orWhere($user->getTable() . '.lastname', 'like', '%' . $filter . '%');
                    });
                })
                ->orWhere(function ($q) use ($filter, $user) {
                    $q->whereHas('group.users', function ($query) use ($filter, $user) {
                        $query->where($user->getTable() . '.firstname', 'like', '%' . $filter . '%')
                            ->orWhere($user->getTable() . '.lastname', 'like', '%' . $filter . '%');
                    });
                });
        }

        $assignees = $query->get();
        $information = [];

        $assignee = new TaskUser();

        foreach ($assignees as $currentAssignee) {
            if (!empty($currentAssignee->user)) {
                $assignee->assign_uid = $currentAssignee->user->uid;
                $assignee->assign_name = $currentAssignee->user->firstname;
                $assignee->assign_lastname = $currentAssignee->user->lastname;
                $assignee->assign_username = $currentAssignee->user->username;
                $assignee->assign_type = User::TYPE;
                $information[] = $assignee;
            }
            if (!empty($currentAssignee->group)) {
                foreach ($currentAssignee->group->users as $user) {
                    $assignee->assign_uid = $user->uid;
                    $assignee->assign_name = $user->firstname;
                    $assignee->assign_lastname = $user->lastname;
                    $assignee->assign_username = $user->username;
                    $assignee->assign_type = User::TYPE;
                    $information[] = $assignee;
                }
            }
        }

        $limit = $options['per_page'];
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $collection = new Collection($information);
        $currentPageResults = $collection->slice(($currentPage - 1) * $limit, $limit)->all();

        return new LengthAwarePaginator($currentPageResults, count($collection), $limit);
    }

    /**
     * Add label with the count of users in the group
     *
     * @param Group $group
     *
     * @return string
     */
    private function labelGroup(Group $group): string
    {
        $name = $group->title . ' ' . $group::STATUS_INACTIVE;
        if ($group->status !== $group::STATUS_INACTIVE) {
            $name = $group->title . ' (' . $group->users()->count() . ') ';
        }
        return $name;
    }

    /**
     * Format data response
     *
     * @param TaskUser $assigned
     *
     * @return TaskUser
     */
    private function formatAssigneeData(TaskUser $assigned): TaskUser
    {
        $assigned->assign_uid = '';
        $assigned->assign_name = '';
        $assigned->assign_lastname = '';
        $assigned->assign_username = '';
        $assigned->assign_type = $assigned->task_users_type;

        if (!empty($assigned->user)) {
            $assigned->assign_uid = $assigned->user->uid;
            $assigned->assign_name = $assigned->user->firstname;
            $assigned->assign_lastname = $assigned->user->lastname;
            $assigned->assign_username = $assigned->user->username;
            $assigned->assign_type = strtolower(User::TYPE);
        }
        if (!empty($assigned->group)) {
            $assigned->assign_uid = $assigned->group->uid;
            $assigned->assign_name = $assigned->group->title;
            $assigned->assign_username = $assigned->group->title;
            $assigned->assign_type = strtolower(Group::TYPE);
        }

        return $assigned;
    }

    /**
     * Generates a query which uses the filter parameter to filter the users and groups
     *
     * @param Builder $query
     * @param string $filter
     *
     * @return Builder
     */
    private function generateFilterUserGroup($query, string $filter): Builder
    {
        $user = new User();
        $group = new Group();
        return $query->where(function ($q) use ($filter, $user) {
            $q->whereHas('user', function ($query) use ($filter, $user) {
                $query->where($user->getTable() . '.firstname', 'like', '%' . $filter . '%')
                    ->orWhere($user->getTable() . '.lastname', 'like', '%' . $filter . '%');
            });
        })
            ->orWhere(function ($q) use ($filter, $group) {
                $q->whereHas('group', function ($query) use ($filter, $group) {
                    $query->where($group->getTable() . '.title', 'like', '%' . $filter . '%');
                });
            });
    }
}
