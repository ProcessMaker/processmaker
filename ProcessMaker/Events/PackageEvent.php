<?php

namespace ProcessMaker\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Support\Collection;

class PackageEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $packages;

    /**
     * Create a new event instance.
     *
     * @param Collection $packages
     */
    public function __construct(Collection $packages)
    {
        $this->packages = $packages;
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
