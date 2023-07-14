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

    private EnvironmentVariable $enVariable;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(array $data)
    {
        $this->enVariable = EnvironmentVariable::where('name', $data['name'])->first();
    }

    /**
     * Get specific data related to the event
     *
     * @return array
     */
    public function getData(): array
    {
        return [
            'name' => [
                'label' => $this->enVariable->getAttribute('name'),
                'link' => route('environment-variables.edit', $this->enVariable),
            ],
            'description' => $this->enVariable->getAttribute('description'),
            'created_at' => $this->enVariable->getAttribute('created_at'),
        ];
    }

    /**
     * Get specific changes without format related to the event
     *
     * @return array
     */
    public function getChanges(): array
    {
        return [
            'id' => $this->enVariable->getAttribute('name'),
        ];
    }

    /**
     * Get the Event name with the syntax ‘[Past-test Action] [Object]’
     *
     * @return string
     */
    public function getEventName(): string
    {
        return 'EnvironmentVariablesCreated';
    }
}
