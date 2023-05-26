<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class CategoryUpdated implements SecurityLogEventInterface
{
    use Dispatchable, FormatSecurityLogChanges;

    private ProcessCategory $enVariable;

    private array $changes;
    private array $original;
    
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(ProcessCategory $data, array $changes, array $original)
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
                'link' => route('processes.index', $this->enVariable) . '#nav-categories',
            ],
            'last_modified' => $this->enVariable->getAttribute('updated_at'),
        ], $this->formatChanges($this->changes, $this->original));
    }

    /**
     * Get the Event name with the syntax ‘[Past-test Action] [Object]’
     *
     * @return string
     */
    public function getEventName(): string
    {
        return 'CategoryUpdated';
    }

    /**
     * Get specific changes without format related to the event
     *
     * @return array
     */
    public function getChanges(): array
    {
        return $this->formatChanges($this->changes, $this->original);
    }
}
