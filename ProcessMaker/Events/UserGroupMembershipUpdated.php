<?php

namespace ProcessMaker\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Http\Controllers\Admin\GroupController;
use ProcessMaker\Http\Controllers\Api\GroupController as ApiGroupController;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\User;

class UserGroupMembershipUpdated implements SecurityLogEventInterface
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $userUpdated;
    public $data;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Array $data, User $userUpdated)
    {
        $this->user = Auth::user();
        $this->userUpdated = $userUpdated;
        $this->buildData($data);
    }

    /**
     * Building the data
     */
    public function buildData($data) {
        
        $groups_deleted = [];
        $groups_added = [];
        
        foreach ($data['attached'] as $group_id) {
            $group = Group::findOrFail($group_id);
            $groups_added[] = [
                'link' => route('groups.edit', $group),
                'label' => $group->name
            ];
        }
        foreach ($data['detached'] as $group_id) {
            $group = Group::findOrFail($group_id);
            $groups_deleted[] = [
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

        if (!empty($groups_added)) {
            $this->data['+ groups'] = $groups_added;
        };

        if (!empty($groups_deleted)) {
            $this->data['- groups'] = $groups_deleted;
        };
    }
    
    public function getData(): array
    {
        return $this->data;
    }

    public function getEventName(): string
    {
        return 'UserGroupMembershipUpdated';
    }
}
