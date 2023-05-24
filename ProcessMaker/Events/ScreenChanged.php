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
use ProcessMaker\Models\Screen;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class ScreenChanged implements SecurityLogEventInterface
{
    use Dispatchable, InteractsWithSockets, SerializesModels, FormatSecurityLogChanges;

    
    private Screen $screen;
    private Request $request;
    public $new_screen_data;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Screen $screen,Request $request)
    {
        $this->request = $request;
        $this->screen = $screen;
    }

    public function getData(): array
    {   
        $old_screen = array_intersect_key($this->screen->getOriginal(), array_flip(['title', 'description','screen_category_id','updated_at','created_at']));
        $old_screen['created_at'] = date('Y-m-d H:i:s', strtotime($old_screen['created_at']));
        $old_screen['updated_at'] = date('Y-m-d H:i:s', strtotime($old_screen['updated_at']));
        $new_screen = array_intersect_key($this->screen->getAttributes(), array_flip(['title', 'description','screen_category_id','updated_at','created_at']));
        $new_screen['created_at'] = date('Y-m-d H:i:s', strtotime($new_screen['created_at']));
        $new_screen['updated_at'] = date('Y-m-d H:i:s', strtotime($new_screen['updated_at']));
        $this->new_screen_data = array_intersect_key($this->screen->getAttributes(), array_flip(['title', 'description','screen_category_id']));


            return array_merge([
                'title' => $new_screen['title'],
            ], $this->formatChanges($new_screen,$old_screen));


    }

    public function getEventName(): string
    {
        return 'ScreenChanged';
    }

    public function getChanges(): array
    {
        return $this->new_screen_data;
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
