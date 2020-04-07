<?php
namespace ProcessMaker\Events;

use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Broadcasting\PrivateChannel;

class BuildScriptExecutor implements ShouldBroadcastNow {
    public $output;
    public $userId;
    public $status;

    public function __construct($output, $userId, $status)
    {
        $this->output = $output;
        $this->userId = $userId;
        $this->status = $status;
    }

    public function broadcastAs() {
        return 'BuildScriptExecutor';
    }

    public function broadcastOn()
    {
        return new PrivateChannel('ProcessMaker.Models.User.' . $this->userId);
    }
}