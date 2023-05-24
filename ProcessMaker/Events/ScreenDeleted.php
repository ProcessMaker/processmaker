<?php

namespace ProcessMaker\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\Screen;

class ScreenDeleted implements SecurityLogEventInterface
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private Screen $screen;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Screen $screen)
    {
        $this->screen =  $screen;
    }

    public function getData(): array
    {   

       return [
         'title' => $this->screen->getAttributes()['title'],
         'description' => $this->screen->getAttributes()['description']             
       ];

    }

    public function getEventName(): string
    {
        return 'ScreenDeleted';
    }

    public function getChanges(): array
    {
        return $this->screen->getAttributes();
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
