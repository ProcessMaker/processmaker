<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class CategoryCreated implements SecurityLogEventInterface
{
    use Dispatchable, FormatSecurityLogChanges;

    private ProcessCategory $enVariable;
    private $variable = [];
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(array $data)
    {
        $this->variable = $data;
        $this->enVariable = ProcessCategory::where('name', $data['name'])->first();
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
                'label' => $this->variable['name'],
                'link' => route('processes.create', $this->enVariable),
            ],
            'name' => $this->variable['name'],
            'created_at' => $this->enVariable->getAttribute('created_at'),
        ];
    }

    /**
     * Get the Event name with the syntax ‘[Past-test Action] [Object]’
     *
     * @return string
     */
    public function getEventName(): string
    {
        return 'CategoryCreated';
    }

    /**
     * Get specific changes without format related to the event
     *
     * @return array
     */
    public function getChanges(): array
    {
        return [
            $this->enVariable
        ];
    }
}
