<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class EnvironmentVariablesUpdated implements SecurityLogEventInterface
{
    use Dispatchable;
    use FormatSecurityLogChanges;

    private EnvironmentVariable $enVariable;

    private array $changes;
    private array $original;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(EnvironmentVariable $data, array $changes, array $original)
    {
        $this->enVariable = $data;
        $this->changes = $changes;
        $this->original = $original;
    }

    /**
     * Get specific data related to the event
     *
     * @return array
     */
    public function getData(): array
    {
        return array_merge([
            'name' => [
                'label' => $this->enVariable->getAttribute('name'),
                'link' => route('environment-variables.edit', $this->enVariable),
            ],
            'last_modified' => $this->enVariable->getAttribute('updated_at'),
        ], $this->formatChanges($this->changes, $this->original));
    }

    /**
     * Get specific changes without format related to the event
     *
     * @return array
     */
    public function getChanges(): array
    {
        return [
            'id' => $this->enVariable->getAttribute('id')
        ];
    }

    /**
     * Get the Event name with the syntax ‘[Past-test Action] [Object]’
     *
     * @return string
     */
    public function getEventName(): string
    {
        return 'EnvironmentVariablesUpdated';
    }
}
