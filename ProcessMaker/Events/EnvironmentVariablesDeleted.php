<?php

namespace ProcessMaker\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class EnvironmentVariablesDeleted implements SecurityLogEventInterface
{
    use Dispatchable;
    use FormatSecurityLogChanges;

    private EnvironmentVariable $enVariable;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(EnvironmentVariable $data)
    {
        $this->enVariable = $data;
    }

    /**
     * Get specific data related to the event
     *
     * @return array
     */
    public function getData(): array
    {
        return [
            'name' => $this->enVariable->getAttribute('name'),
            'description' => $this->enVariable->getAttribute('description'),
            'deleted_at' => Carbon::now()
        ];
    }

    /**
     * Get specific changes without format related to the event
     *
     * @return array
     */
    public function getChanges(): array
    {
        return $this->enVariable->getAttributes();
    }

    /**
     * Get the Event name with the syntax ‘[Past-test Action] [Object]’
     *
     * @return string
     */
    public function getEventName(): string
    {
        return 'EnvironmentVariablesDeleted';
    }
}
