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

class ScriptResponseEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;

    public $status;

    public $response;

    public $watcher;

    public $nonce;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, $status, array $response, $watcher = null, $nonce = null)
    {
        $this->userId = $user->id;
        $this->status = $status;
        $this->response = $response;
        $this->watcher = $watcher;
        $this->nonce = $nonce;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('ProcessMaker.Models.User.' . $this->userId);
    }

    /**
     * Cache the script response to be loaded by API
     *
     * @return string
     */
    private function cacheResponse()
    {
        $key = uniqid('srn', true);
        Cache::put("srn.$key", $this->response, now()->addMinutes(1));

        return ['key' => $key];
    }

    public function broadcastWith()
    {
        $date = new Carbon();
        $response = $this->cacheResponse($this->response);

        return [
            'type' => '.' . \get_class($this),
            'name' => __('Script executed'),
            'dateTime' => $date->toIso8601String(),
            'status' => $this->status,
            'watcher' => $this->watcher,
            'response' => $response,
            'nonce' => $this->nonce,
        ];
    }
}
