<?php

namespace ProcessMaker\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\Models\ProcessRequest;

class RedirectToEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $payloadUrl;

    /**
     * Create a new event instance.
     */
    public function __construct(
        private ProcessRequest $processRequest,
        public string $method,
        public array $params
    ) {
        //
    }

    /**
     * Set the event name
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'RedirectTo';
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            // Current request
            new PrivateChannel('ProcessMaker.Models.ProcessRequest.' . $this->processRequest->getKey())
        ];
        // include child requests if any
        foreach($this->processRequest->childRequests()->pluck('id') as $childRequestId) {
            $channels[] = new PrivateChannel('ProcessMaker.Models.ProcessRequest.' . $childRequestId);
        }
        // include parent request if any
        if ($this->processRequest->parent_request_id) {
            $channels[] = new PrivateChannel('ProcessMaker.Models.ProcessRequest.' . $this->processRequest->parent_request_id);
        }
        return $channels;
    }
}
