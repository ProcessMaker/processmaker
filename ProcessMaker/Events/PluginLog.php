<?php

namespace ProcessMaker\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/**
 * Event dispatched during plugin operations to provide real-time logging.
 */
class PluginLog implements ShouldBroadcastNow
{
    /**
     * The log message.
     *
     * @var string
     */
    public $message;

    /**
     * The status: 'running', 'done', or 'error'.
     *
     * @var string
     */
    public $status;

    /**
     * The plugin name (optional).
     *
     * @var string|null
     */
    public $pluginName;

    /**
     * The user ID to send the broadcast event to.
     *
     * @var int
     */
    public $userId;

    /**
     * Create a new event instance.
     *
     * @param string $message
     * @param string $status
     * @param string|null $pluginName
     */
    public function __construct(string $message, string $status, ?string $pluginName = null, ?int $userId = null)
    {
        $this->message = $message;
        $this->status = $status;
        $this->pluginName = $pluginName;
        $this->userId = $userId;
    }

    public function broadcastAs()
    {
        return 'PluginLog';
    }

    public function broadcastOn()
    {
        return new PrivateChannel('ProcessMaker.Models.User.' . $this->userId);
    }
}
