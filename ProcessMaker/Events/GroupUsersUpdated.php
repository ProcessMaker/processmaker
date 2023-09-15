<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\User;

class GroupUsersUpdated implements SecurityLogEventInterface
{
    use Dispatchable;

    const ADDED = 'added';

    const DELETED = 'deleted';

    private array $data;

    private int $groupUpdated;

    private User|Group $member;

    private string $action;

    private array $changes;

    private string $memberType;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(int $groupUpdated, int $memberId, string $action, string $memberType)
    {
        $this->groupUpdated = $groupUpdated;
        $this->action = $action;
        $this->memberType = $memberType;
        $this->member = $memberType::find($memberId);
        $this->buildData();
        $this->changes = [
            'memberType' => $memberType,
            'memberId' => $memberId,
            'group' => $groupUpdated,
            'action' => $action,
        ];
    }

    /**
     * Building the data
     */
    public function buildData()
    {
        $group = Group::findOrFail($this->groupUpdated);

        if ($this->memberType === User::class) {
            $type = 'user';
            $link = 'users.edit';
            $label = $this->member->username;
        } else {
            $type = 'group';
            $link = 'groups.edit';
            $label = $this->member->name;
        }

        switch ($this->action) {
            case self::ADDED:
                $this->data = [
                    'group' => [
                        'link' => route('groups.edit', $group),
                        'label' => $group->name,
                    ],
                    '+ ' . $type => [
                        'link' => route($link, $this->member),
                        'label' => $label,
                    ],
                ];
                break;
            case self::DELETED:
                $this->data = [
                    'group' => [
                        'link' => route('groups.edit', $group),
                        'label' => $group->name,
                    ],
                    '- ' . $type => [
                        'link' => route($link, $this->member),
                        'label' => $label,
                    ],
                ];
                break;
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
        return 'GroupMembersUpdated';
    }
}
