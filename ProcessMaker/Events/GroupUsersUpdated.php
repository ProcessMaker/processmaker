<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\User;

class GroupUsersUpdated implements SecurityLogEventInterface
{
    use Dispatchable;

    public array $data;
    public int $groupUpdated;
    public User|Group $member;
    public string $action;
    public array $changes;
    public string $memberType;

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
            'action' => $action
        ];
    }

    /**
     * Building the data
     */
    public function buildData() {
        $group = Group::findOrFail($this->groupUpdated);
        
        if ($this->memberType === "ProcessMaker\Models\User") {
            $type = 'user';
            $link = 'users.edit';
        } else {
            $type = 'group';
            $link = 'groups.edit';
        } 

        switch ($this->action) {
            case 'added':
                $this->data = [
                    'group' => [
                        'link' => route('groups.edit', $group),
                        'label' => $group->name
                    ],
                    '+ ' . $type => [
                        'link' => route($link, $this->member),
                        'label' => $this->member->name
                    ]
                ];
                break;
            case 'deleted':
                $this->data = [
                    'group' => [
                        'link' => route('groups.edit', $group),
                        'label' => $group->name
                    ],
                    '- ' . $type => [
                        'link' => route( $link, $this->member),
                        'label' => $this->member->name
                    ]
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
        return 'User Added/Removed from Group';
    }
}
