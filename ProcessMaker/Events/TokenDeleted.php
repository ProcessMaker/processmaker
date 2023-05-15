<?php

namespace ProcessMaker\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\Models\User;

class TokenDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $deleted_values;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, $deleted_values)
    {
        $this->user = $user;
        $this->deleted_values = $deleted_values;
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
