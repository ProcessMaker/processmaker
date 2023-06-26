<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\User;

class UserGroupMembershipUpdated implements SecurityLogEventInterface
{
    use Dispatchable;

    private User $userUpdated;

    private array $data;

    private array $changes;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(array $data, User $userUpdated)
    {
        $this->userUpdated = $userUpdated;
        $this->buildData($data);
        $this->changes = [
            'user' => $userUpdated->id,
            'groups' => $data,
        ];
    }

    /**
     * Building the data
     */
    public function buildData($data)
    {
        $groupsDeleted = [];
        $groupsAdded = [];

        if (!empty($data['attached'])) {
            foreach ($data['attached'] as $groupId) {
                $group = Group::findOrFail($groupId);
                $groupsAdded[] = [
                    'link' => route('groups.edit', $group),
                    'label' => $group->name,
                ];
            }
        }
        if (!empty($data['detached'])) {
            foreach ($data['detached'] as $groupId) {
                $group = Group::findOrFail($groupId);
                $groupsDeleted[] = [
                    'link' => route('groups.edit', $group),
                    'label' => $group->name,
                ];
            }
        }

        $this->data = [
            'user' => [
                'link' => route('users.edit', $this->userUpdated),
                'label' => $this->userUpdated->username,
            ],
        ];

        if (!empty($groupsAdded)) {
            $this->data['+ groups'] = $groupsAdded;
        }

        if (!empty($groupsDeleted)) {
            $this->data['- groups'] = $groupsDeleted;
        }
    }

    /**
     * Return event data
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Return event changes
     */
    public function getChanges(): array
    {
        return $this->changes;
    }

    /**
     * return event name
     */
    public function getEventName(): string
    {
        return 'UserGroupsUpdated';
    }
}
