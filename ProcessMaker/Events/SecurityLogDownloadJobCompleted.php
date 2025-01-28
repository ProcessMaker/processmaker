<?php

namespace ProcessMaker\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\Models\User;

class SecurityLogDownloadJobCompleted implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public $user;

    private bool $success;

    private ?string $link;

    private ?string $message;

    /**
     * Create a new event instance.
     *
     * @param User $user
     * @param bool $success
     * @param string $message
     * @param string|null $link
     */
    public function __construct(User $user, bool $success, string $message, string $link = null)
    {
        $this->user = $user;
        $this->success = $success;
        $this->message = $message;
        $this->link = $link;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel("ProcessMaker.Models.User.{$this->user->id}");
    }

    /**
     * Set the event name
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'SecurityLogDownloadJobDone';
    }

    /**
     * Set the data to broadcast with this event
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'link' => $this->link,
        ];
    }
}
