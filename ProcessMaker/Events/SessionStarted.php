<?php

namespace ProcessMaker\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\Facades\RequestDevice;
use ProcessMaker\Models\User;
use Session;

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
    public function broadcastAs()
    {
        return 'SessionStarted';
    }

    /**
     * Set the data to broadcast with this event
     *
     * @return array
     */
    public function broadcastWith()
    {
        $lifetime = Session::has('rememberme') && Session::get('rememberme')
                        ? 'Number.MAX_SAFE_INTEGER'
                        : config('session.lifetime');

        return [
            'lifetime' => $lifetime,
            'device_id' => RequestDevice::getId(),
            'device_variable' => RequestDevice::getVariableName(),
        ];
    }
}
