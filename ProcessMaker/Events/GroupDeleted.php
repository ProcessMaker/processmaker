<?php

namespace ProcessMaker\Events;

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
    }

    /**
     * Get specific data related to the event
     *
     * @return array
     */
    public function getData(): array
    {
        // Get information about the users assigned in the group
        $usersInfo = [];
        foreach ($this->userIds as $user) {
            $user = User::find($user['member_id']);
            $this->userMembers[] = $user;
            $usersInfo[] = [
                'username' => [
                    'label' => $user->username,
                    'link' => route('users.edit', $user),
                ]
            ];
        }
        // Get information about the groups assigned in the group
        $groupsInfo = [];
        foreach ($this->groupIds as $group) {
            $group = Group::find($group['member_id']);
            $this->groupMembers[] = $group;
            $groupsInfo[] = [
                'name' => [
                    'label' => $group->name,
                    'link' => route('groups.edit', $group),
                ]
            ];
        }

        return [
            'name' => $this->group->getAttribute('name'),
            'user_members' => [
                $usersInfo
            ],
            'group_members' => [
                $groupsInfo
            ]
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
