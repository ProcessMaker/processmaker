<?php

namespace ProcessMaker\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\Models\ProcessRequest;

class RedirectToEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        private ProcessRequest $processRequest,
        public string $method,
        public array $params,
    ) {
        //
    }

    /**
     * Get the Event name with the syntax ‘[Past-test Action] [Object]’
     *
     * @return string
     */
    public function getEventName(): string
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
        return [
            new PrivateChannel('ProcessMaker.Models.ProcessRequest.' . $this->processRequest->getKey()),
        ];
    }
}
