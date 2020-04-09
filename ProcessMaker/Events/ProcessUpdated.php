<?php

namespace ProcessMaker\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use ProcessMaker\Models\ProcessRequest;

class ProcessUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $payloadUrl;
    public $event;
    
    private $processRequest;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(ProcessRequest $processRequest, $event)
    {
        $this->payloadUrl = route('api.requests.show', ['request' => $processRequest->getKey()]);
        $this->event = $event;
        
        $this->processRequest = $processRequest;
    }

    /**
     * Set the event name
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'ProcessUpdated';
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
     * @return \ProcessMaker\Models\ProcessRequest
     */    
    public function getProcessRequest()
    {
        return $this->processRequest;
    }
}
