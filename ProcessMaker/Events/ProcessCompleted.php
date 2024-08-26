<?php

namespace ProcessMaker\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\Models\ProcessRequest;

class ProcessCompleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $payloadUrl;

    private $processRequest;

    public $endEventDestination;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(ProcessRequest $processRequest)
    {
        $this->payloadUrl = route('api.requests.show', ['request' => $processRequest->getKey()]);
        $this->processRequest = $processRequest;

        if ($processRequest->asset_type !== 'GUIDED_HELPER_PROCESS') {
            $this->endEventDestination = $processRequest->getElementDestination();
        }
    }

    /**
     * Set the event name
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'ProcessCompleted';
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
