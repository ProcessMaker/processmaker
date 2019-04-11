<?php

namespace ProcessMaker\Events;

use ProcessMaker\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class SessionStarted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public $user;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel("ProcessMaker.Models.User.{$this->user->id}");
    }
    
    /**
     * Set the event name
     *
     * @return string
     */
    public function broadcastAs() {
        return 'SessionStarted';
    }

    /**
     * Set the data to broadcast with this event
     *
     * @return array
     */    
    public function broadcastWith() {
        return ['lifetime' => config('session.lifetime')];
    }
}
