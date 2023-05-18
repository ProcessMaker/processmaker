<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class EnvironmentVariablesCreated implements SecurityLogEventInterface
{
    use Dispatchable;
    use FormatSecurityLogChanges;

    private $variables = [];

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($newData)
    {
        $this->variables = $newData;
    }

    /**
     * Get specific data related to the event
     *
     * @return array
     */
    public function getData(): array
    {
        return [$this->variables];
    }

    /**
     * Get the Event name with the syntax ‘[Past-test Action] [Object]’
     *
     * @return string
     */
    public function getEventName(): string
    {
        return 'Created Environment Variables';
    }
}
