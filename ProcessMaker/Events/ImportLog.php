<?php

namespace ProcessMaker\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ImportLog implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public $userId,
        public $type,
        public $message,
        public $additionalParams = []
    ) {
    }

    public function broadcastAs()
    {
        return 'ImportLog';
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('ProcessMaker.Models.User.' . $this->userId),
        ];
    }
}
