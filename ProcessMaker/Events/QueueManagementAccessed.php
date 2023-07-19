<?php

namespace ProcessMaker\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;

class QueueManagementAccessed implements SecurityLogEventInterface
{
    use Dispatchable;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Get specific data related to the event
     *
     * @return array
     */
    public function getData(): array
    {
        return [
            'name' => 'Laravel Horizon',
            'accessed_at' => Carbon::now(),
        ];
    }

    /**
     * Get specific changes without format related to the event
     *
     * @return array
     */
    public function getChanges(): array
    {
        return [];
    }

    /**
     * Get the Event name with the syntax ‘[Past-test Action] [Object]’
     *
     * @return string
     */
    public function getEventName(): string
    {
        return 'QueueManagementAccessed';
    }
}
