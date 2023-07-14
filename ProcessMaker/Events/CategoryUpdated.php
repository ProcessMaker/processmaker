<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class CategoryUpdated implements SecurityLogEventInterface
{
    use Dispatchable;
    use FormatSecurityLogChanges;

    private ProcessCategory $category;

    private array $changes;

    private array $original;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(ProcessCategory $data, array $changes, array $original)
    {
        $this->category = $data;
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
                'label' => $this->category->getAttribute('name'),
                'link' => route('processes.index', $this->category) . '#nav-categories',
            ],
            'last_modified' => $this->category->getAttribute('updated_at'),
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
            'id' => $this->category->getAttribute('id'),
        ];
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
}
