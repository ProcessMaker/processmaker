<?php

namespace ProcessMaker\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use ProcessMaker\Models\ProcessRequestToken;

class ActivityAssigned implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $payloadUrl;
    
    private $processRequest, $processRequestToken;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(ProcessRequestToken $token)
    {
        $this->payloadUrl = route('api.tasks.show', ['task' => $token->id]);
        $this->processRequest = $token->processRequest;
        $this->processRequestToken = $token;
    }

    /**
     * Set the event name
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'ActivityAssigned';
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('ProcessMaker.Models.ProcessRequest.' . $this->processRequest->getKey());
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
