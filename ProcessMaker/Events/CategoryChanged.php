<?php

namespace ProcessMaker\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\ProcessCategory;

class CategoryChanged implements SecurityLogEventInterface
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private Request $request;
    private ProcessCategory $processCategory;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Request $request,ProcessCategory $processCategory)
    {
        $this->request = $request;
        $this->processCategory =  $processCategory; 
    }

    public function getData(): array
    {   
       return [
        '+ name' => $this->processCategory->getAttributes()['name'],
        '+ status' => $this->processCategory->getAttributes()['status'],
        '- name' => $this->processCategory->getOriginal()['name'],
        '- status' => $this->processCategory->getOriginal()['status']               
       ];

    }

    public function getEventName(): string
    {
        return 'CategoryChanged';
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
