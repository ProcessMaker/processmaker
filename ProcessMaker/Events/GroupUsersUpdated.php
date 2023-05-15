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
use ProcessMaker\Models\Group;
use ProcessMaker\Models\User;

class GroupUsersUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $data;
    public $groupUpdated;
    public $user_id;
    public $action;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(int $groupUpdated, int $user_id, string $action)
    {
        $this->user = Auth::user();
        $this->groupUpdated = $groupUpdated;
        $this->user_id = $user_id;
        $this->action = $action;
        $this->buildData();
    }

    /**
     * Building the data
     */
    public function buildData() {
        $user = User::findOrFail($this->user_id);
        $group = Group::findOrFail($this->groupUpdated);

        switch ($this->action) {
            case 'added':
                $this->data = [
                    'Group name' => $group->name,
                    'User added' => $user->username
                ];
                break;
            case 'deleted':
                $this->data = [
                    'Group name' => $group->name,
                    'User deleted' => $user->username
                ];
                break;
        }
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
