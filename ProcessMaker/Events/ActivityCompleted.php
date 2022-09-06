<?php

namespace ProcessMaker\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\Models\ProcessRequestToken;

class ActivityCompleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $payloadUrl;

    private $processRequestToken;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(ProcessRequestToken $processRequestToken)
    {
        $this->payloadUrl = route('api.tasks.show', ['task' => $processRequestToken->id]);
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

    /**
     * Return the process request.
     *
     * @return \ProcessMaker\Models\ProcessRequestToken
     */
    public function getProcessRequestToken()
    {
        return $this->processRequestToken;
    }
}
