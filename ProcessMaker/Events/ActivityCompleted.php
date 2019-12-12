<?php

namespace ProcessMaker\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use ProcessMaker\Models\ProcessRequestToken;

class ActivityCompleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $processRequestToken;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(ProcessRequestToken $processRequestToken)
    {
        $this->processRequestToken = $processRequestToken;
    }

    /**
     * Set the event name
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'ActivityCompleted';
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('ProcessMaker.Models.ProcessRequestToken.' . $this->processRequestToken->getKey());
    }
}
