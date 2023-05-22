<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\User;

class GroupUsersUpdated implements SecurityLogEventInterface
{
    use Dispatchable;

    public $data;
    public $groupUpdated;
    public $userId;
    public $action;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(int $groupUpdated, int $userId, string $action)
    {
        $this->groupUpdated = $groupUpdated;
        $this->userId = $userId;
        $this->action = $action;
        $this->buildData();
    }

    /**
     * Building the data
     */
    public function buildData() {
        $user = User::findOrFail($this->userId);
        $group = Group::findOrFail($this->groupUpdated);

        switch ($this->action) {
            case 'added':
                $this->data = [
                    'group' => [
                        'link' => route('groups.edit', $group),
                        'label' => $group->name
                    ],
                    '+ user' => [
                        'link' => route('users.edit', $user),
                        'label' => $user->username
                    ]
                ];
                break;
            case 'deleted':
                $this->data = [
                    'group' => [
                        'link' => route('groups.edit', $group),
                        'label' => $group->name
                    ],
                    '- user' => [
                        'link' => route('users.edit', $user),
                        'label' => $user->username
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
     * return event name
     */
    public function getEventName(): string
    {
        return 'GroupUsersUpdated';
    }
}
