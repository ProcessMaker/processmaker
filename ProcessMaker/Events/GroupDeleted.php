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
    private $userMembers = [];
    private $groupMembers = [];

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Group $dataDeleted, $users = [], $groups = [])
    {
        $this->group = $dataDeleted;
        $this->userMembers = $users;
        $this->groupMembers = $groups;
    }

    /**
     * Get specific data related to the event
     *
     * @return array
     */
    public function getData(): array
    {
        $userMembersInfo = [];
        foreach ($this->userMembers as $user) {
            $user = User::find($user['member_id']);
            $userMembersInfo[] = [
                'username' => [
                    'label' => $user->username,
                    'link' => route('users.edit', $user),
                ]
            ];
        }
        $groupMembersInfo = [];
        foreach ($this->groupMembers as $group) {
            $group = Group::find($group['member_id']);
            $groupMembersInfo[] = [
                'name' => [
                    'label' => $group->name,
                    'link' => route('groups.edit', $group),
                ]
            ];
        }

        return [
            'group_name' => $this->group->getAttribute('name'),
            'user_members' => [
                $userMembersInfo
            ],
            'group_members' => [
                $groupMembersInfo
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
