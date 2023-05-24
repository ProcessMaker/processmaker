<?php

namespace ProcessMaker\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\Contracts\SecurityLogEventInterface;

class TemplateCreated implements SecurityLogEventInterface
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    private $payload;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($payload)
    {
        $this->payload = $payload;
    }

    public function getData(): array
    {  

       return [
           'type' => $this->payload['type'],
           'version' => $this->payload['version'],
           'name' => $this->payload['name']
       ];

    }

    public function getEventName(): string
    {
        return 'TemplateCreated';
    }

    public function getChanges(): array
    {
        
        return [
            'type' => $this->payload['type'],
            'version' => $this->payload['version'],
            'name' => $this->payload['name'],
            'root' => $this->payload['root']
        ];
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
