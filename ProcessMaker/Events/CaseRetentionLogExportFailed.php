<?php

namespace ProcessMaker\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\Models\User;

class CaseRetentionLogExportFailed implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public $user;

    private bool $success;

    private ?string $link;

    private ?string $message;

    public function __construct(User $user, bool $success, string $message, ?string $link = null)
    {
        $this->user = $user;
        $this->success = $success;
        $this->message = $message;
        $this->link = $link;
    }

    public function broadcastOn()
    {
        return new PrivateChannel("ProcessMaker.Models.User.{$this->user->id}");
    }

    public function broadcastAs()
    {
        return 'CaseRetentionLogExportFailed';
    }

    public function broadcastWith()
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'link' => $this->link,
        ];
    }
}
