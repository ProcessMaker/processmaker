<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\User;

class UserGroupMembershipUpdated implements SecurityLogEventInterface
{
    use Dispatchable;

    public $userUpdated;
    public $data;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(array $data, User $userUpdated)
    {
        $this->userUpdated = $userUpdated;
        $this->buildData($data);
    }

    /**
     * Building the data
     */
    public function buildData($data) {
        
        $groupsDeleted = [];
        $groupsAdded = [];
        
        foreach ($data['attached'] as $groupId) {
            $group = Group::findOrFail($groupId);
            $groupsAdded[] = [
                'link' => route('groups.edit', $group),
                'label' => $group->name
            ];
        }
        foreach ($data['detached'] as $groupId) {
            $group = Group::findOrFail($groupId);
            $groupsDeleted[] = [
                'link' => route('groups.edit', $group),
                'label' => $group->name
            ];
        }

        $this->data = [
            'user' => [
                'link' => route('users.edit', $this->userUpdated),
                'label' => $this->userUpdated->username
            ]
        ];

        if (!empty($groupsAdded)) {
            $this->data['+ groups'] = $groupsAdded;
        };

        if (!empty($groupsDeleted)) {
            $this->data['- groups'] = $groupsDeleted;
        };
    }
    
    /**
     * Return event data 
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * return event name
     */
    public function getEventName(): string
    {
        return 'UserGroupMembershipUpdated';
    }
}
