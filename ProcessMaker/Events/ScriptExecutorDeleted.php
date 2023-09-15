<?php

namespace ProcessMaker\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;

class ScriptExecutorDeleted implements SecurityLogEventInterface
{
    use Dispatchable;

    private array $data;

    /**
     * Create a new event instance.
     *
     * @param array $data
     *
     * @return void
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Return event data
     */
    public function getData(): array
    {
        return [
            'name' => $this->data['title'] ?? '',
            'description' => $this->data['description'] ?? '',
            'deleted_at' => Carbon::now(),
        ];
    }

    /**
     * Return event changes
     */
    public function getChanges(): array
    {
        return [
            'id' => $this->data['id'] ?? '',
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
