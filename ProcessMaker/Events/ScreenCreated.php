<?php

namespace ProcessMaker\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use ProcessMaker\Contracts\SecurityLogEventInterface;

class ScreenCreated implements SecurityLogEventInterface
{
    use Dispatchable;

    private Request $request;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->request =  $request;
    }

    public function getData(): array
    {   
        
       return [
            'name' => $this->request->input('title'),
            'description' => $this->request->input('description'),        
            'type' => $this->request->input('type'),
            'screen_category_id' => $this->request->input('screen_category_id')   
       ];

    }

    public function getEventName(): string
    {
        return 'ScreenCreated';
    }

    public function getChanges(): array
    {
       return [
            'name' => $this->request->input('name'),
            'description' => $this->request->input('description'),        
            'type' => $this->request->input('type'),
            'screen_category_id' => $this->request->input('screen_category_id')   
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
