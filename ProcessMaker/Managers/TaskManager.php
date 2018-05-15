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

class TaskManager
{
    
    /**
     * List the users and groups assigned to a task.
     *
     * @param Task $activity
     * @param array $options start|limit|filter
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

        $assignees = $this->paginate($query, $options, $paged);

        foreach ($assignees as $assigned) {
            if (!empty($assigned->group)) {
                $assigned->group->title = $this->labelGroup($assigned->group);
            }
            $assigned = $this->formatDataAssignee($assigned);
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
        foreach ($query->get() as $user) {
            $information[] = [
                'aas_uid' => $user->uid,
                'aas_name' => $user->firstname,
                'aas_lastname' => $user->lastname,
                'aas_username' => $user->username,
                'aas_type' => User::TYPE
            ];
        }

        $query = Group::whereNotIn('id', $usersAssigned);
        if (!empty($options['filter'])) {
            $group = new Group();
            $query->where($group->getTable() . '.title', 'like', '%' . $options['filter'] . '%');
        }
        foreach ($query->get() as $group) {
            $group->title = $this->labelGroup($group);
            $information[] = [
                'aas_uid' => $group->uid,
                'aas_name' => $group->title,
                'aas_lastname' => '',
                'aas_username' => $group->title,
                'aas_type' => Group::TYPE
            ];
        }

        $limit = $options['limit'];
        $currentPage = !$paged ? Paginator::resolveCurrentPage() : LengthAwarePaginator::resolveCurrentPage();
        $collection = new Collection($information);
        $currentPageResults = $collection->slice(($currentPage - 1) * $limit, $limit)->all();
        if (!$paged) {
            return new Paginator($currentPageResults, count($collection), $limit);
        }
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
        $type = User::TYPE;
        switch (strtoupper($options['aas_type'])) {
            case 'USER':
                $check = User::where('uid', $options['aas_uid'])->first();
                $query->onlyUsers();
                break;
            case 'GROUP':
                $check = Group::where('uid', $options['aas_uid'])->first();
                $type = Group::TYPE;
                $query->onlyGroups();
                break;
            default:
                $check = false;
                break;
        }
        if (!$check) {
            throw new TaskAssignedException(__('This id :uid does not correspond to a registered :type', ['uid' => $options['aas_uid'], 'type' => $options['aas_type']]));
        }
        $exist = $query->where('user_id', $check->id)->exists();
        if ($exist) {
            throw new TaskAssignedException(__('This ID: :user is already assigned to task: :task', ['user' => $options['aas_uid'], 'task' => $activity->uid]));
        }
        $assigned = new TaskUser();
        $assigned->task_id = $activity->id;
        $assigned->user_id = $check->id;
        $assigned->task_user_type = $type;
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
        $response = TaskUser::where('task_id', $activity->id)
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

        if (!$response) {
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
        $assigned = TaskUser::where('task_id', $activity->id)
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

        if ($assigned->isEmpty()) {
            Throw new TaskAssignedException(__('Record not found for id: :assignee', ['assignee' => $assignee]));
        }

        return $this->formatDataAssignee($assigned->first());
    }

    /**
     *  Get a list all the users who are assigned to a task (including users that are within groups).
     *
     * @param Task $activity
     * @param array $options
     *
     * @return Paginator
     */
    public function getInformationAllAssignee(Task $activity, $options): Paginator
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

        foreach ($assignees as $assign) {
            if (!empty($assign->user)) {
                $information[] = [
                    'aas_uid' => $assign->user->uid,
                    'aas_name' => $assign->user->firstname,
                    'aas_lastname' => $assign->user->lastname,
                    'aas_username' => $assign->user->username,
                    'aas_type' => User::TYPE
                ];
            }
            if (!empty($assign->group)) {
                foreach ($assign->group->users as $user) {
                    $information[] = [
                        'aas_uid' => $user->uid,
                        'aas_name' => $user->firstname,
                        'aas_lastname' => $user->lastname,
                        'aas_username' => $user->username,
                        'aas_type' => User::TYPE
                    ];
                }
            }
        }

        $limit = $options['limit'];
        $currentPage = Paginator::resolveCurrentPage();
        $collection = new Collection($information);
        $currentPageResults = $collection->slice(($currentPage - 1) * $limit, $limit)->all();

        return new Paginator($currentPageResults, count($collection), $limit);
    }

    /**
     * Add to label group count of users in the group
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
    private function formatDataAssignee(TaskUser $assigned): TaskUser
    {
        $assigned->aas_uid = '';
        $assigned->aas_name = '';
        $assigned->aas_lastname = '';
        $assigned->aas_username = '';
        $assigned->aas_type = $assigned->task_user_type;

        if (!empty($assigned->user)) {
            $assigned->aas_uid = $assigned->user->uid;
            $assigned->aas_name = $assigned->user->firstname;
            $assigned->aas_lastname = $assigned->user->lastname;
            $assigned->aas_username = $assigned->user->username;
            $assigned->aas_type = strtolower(User::TYPE);
        }
        if (!empty($assigned->group)) {
            $assigned->aas_uid = $assigned->group->uid;
            $assigned->aas_name = $assigned->group->title;
            $assigned->aas_username = $assigned->group->title;
            $assigned->aas_type = strtolower(Group::TYPE);
        }

        return $assigned;
    }

    /**
     * Generate eloquent query adding filter in users and groups
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

    /**
     * Get paginate or simple paginate for collection
     *
     * @param Builder $query
     * @param array $parameter
     * @param bool $paged
     *
     * @return LengthAwarePaginator|Paginator
     */
    private function paginate(Builder $query, $parameter, $paged = false)
    {
        if ($paged) {
            return $query->paginate($parameter['limit']);
        }
        return $query->simplePaginate($parameter['limit']);
    }

}
