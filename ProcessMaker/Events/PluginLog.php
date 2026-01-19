<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Event dispatched during plugin operations to provide real-time logging.
 */
class PluginLog
{
    use Dispatchable;

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
     * Create a new event instance.
     *
     * @param string $message
     * @param string $status
     * @param string|null $pluginName
     */
    public function __construct(string $message, string $status, ?string $pluginName = null)
    {
        $this->message = $message;
        $this->status = $status;
        $this->pluginName = $pluginName;
    }
}
