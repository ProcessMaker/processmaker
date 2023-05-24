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

class TemplateDeleted implements SecurityLogEventInterface
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $templateName;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($templateName)
    {
        $this->templateName = $templateName;

    }

    public function getData(): array
    {  

       return [
         'template_name' => $this->templateName        
       ];

    }

    public function getEventName(): string
    {
        return 'TemplateDeleted';
    }

    public function getChanges(): array
    {

        return ['template_name' => $this->templateName];
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
