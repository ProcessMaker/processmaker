<?php

namespace ProcessMaker\Events;

use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use ProcessMaker\Models\User;

class ProcessTranslationChunkProgressEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;

    public $processId;

    public $language;

    public $batch;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($processId, $language, $batch)
    {
        $this->processId = $processId;
        $this->language = $language;
        $this->batch = $batch;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('ProcessMaker.Models.Process.' . $this->processId . '.Language.' . $this->language);
    }

    public function broadcastWith()
    {
        return [
            'batch' => $this->batch,
        ];
    }
}
