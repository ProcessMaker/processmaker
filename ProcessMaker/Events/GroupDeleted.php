<?php

namespace ProcessMaker\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\User;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class GroupDeleted implements SecurityLogEventInterface
{
    use Dispatchable;
    use FormatSecurityLogChanges;

    private Group $group;
    private array $userIds;
    private array $userMembers;
    private array $groupIds;
    private array $groupMembers;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Group $dataDeleted, array $users, array $groups)
    {
        $this->group = $dataDeleted;
        $this->userIds = $users;
        $this->groupIds = $groups;
        // Get information about the users assigned in the group
        $this->userMembers = [];
        foreach ($this->userIds as $user) {
            $user = User::find($user['member_id']);
            $this->userMembers[] = [
                'username' => [
                    'label' => $user->username,
                    'link' => route('users.edit', $user),
                ]
            ];
        }
        // Get information about the groups assigned in the group
        $this->groupMembers = [];
        foreach ($this->groupIds as $group) {
            $group = Group::find($group['member_id']);
            $this->groupMembers[] = [
                'name' => [
                    'label' => $group->name,
                    'link' => route('groups.edit', $group),
                ]
            ];
        }
    }

    /**
     * Get specific data related to the event
     *
     * @return array
     */
    public function getData(): array
    {
        return [
            'name' => $this->group->getAttribute('name'),
            'user_members' => [
                $this->userMembers
            ],
            'group_members' => [
                $this->groupMembers
            ],
            'deleted_at' => Carbon::now()
        ];
    }

    /**
     * Get specific changes without format related to the event
     *
     * @return array
     */
    public function getChanges(): array
    {
        return [
            'group_name' => [
                $this->group
            ],
            'user_members' => [
                $this->userMembers
            ],
            'group_members' => [
                $this->groupMembers
            ]
        ];
    }

    /**
     * Get the Event name with the syntax ‘[Past-test Action] [Object]’
     *
     * @return string
     */
    public function getEventName(): string
    {
        return 'GroupDeleted';
    }
}
