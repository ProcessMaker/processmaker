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
use ProcessMaker\Models\ProcessCategory;

class CategoryDeleted implements SecurityLogEventInterface
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private ProcessCategory $processCategory;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(ProcessCategory $processCategory)
    {
        $this->processCategory =  $processCategory;
    }

    public function getData(): array
    {   

       return [
         'category_deleted' => $this->processCategory->getAttribute('name')         
       ];

    }

    public function getEventName(): string
    {
        return 'CategoryDeleted';
    }

    public function getChanges(): array
    {
        return [
            'id' => $this->processCategory->getAttribute('id'),
            'name' => $this->processCategory->getAttribute('name'),
            'status' => $this->processCategory->getAttribute('status')
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
