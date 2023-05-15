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
use ProcessMaker\Models\User;

class AuthClientUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $original_values;
    public $changed_values;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($original_values, $changed_values)
    {
        $this->user = Auth::user();
        $this->original_values = $original_values;
        $this->changed_values = $changed_values;
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
