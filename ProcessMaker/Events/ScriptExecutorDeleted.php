<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;

class ScriptExecutorDeleted implements SecurityLogEventInterface
{
    use Dispatchable;

    private array $data;
    private array $changes;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(array $deleted_values)
    {
        $this->changes = $deleted_values;
    }

    /**
     * Return event data
     */
    public function getData(): array
    {
        return [
            'script_executor_id' => $this->changes['id'] ?? '',
            'title' => $this->changes['title'] ?? '',
            'description' => $this->changes['description'] ?? ''
        ];
    }

    /**
     * Return event changes
     */
    public function getChanges(): array
    {
        return [
            'id' => $this->changes['id'] ?? ''
        ];
    }

    /**
     * return event name
     */
    public function getEventName(): string
    {
        return 'ScriptExecutorDeleted';
    }
}
