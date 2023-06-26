<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\Process;

class ProcessCreated implements SecurityLogEventInterface
{
    use Dispatchable;

    private Process $process;

    private string $typeCreation;

    public const BLANK_CREATION = 'BLANK';

    public const TEMPLATE_CREATION = 'TEMPLATE';

    /**
     * Create a new event instance.
     *
     * @param Process $process
     */
    public function __construct(Process $process, string $typeCreation = '')
    {
        $this->process = $process;
        $this->typeCreation = $typeCreation;
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
                'label' => $this->process->getAttribute('name'),
                'link' => route('modeler.show', $this->process),
            ],
            'description' => $this->process->getAttribute('description'),
            'category' => $this->process->category ? $this->process->category->name : null,
            'process_manager' => $this->process->user ? [
                'label' => $this->process->user->getAttribute('fullname'),
                'link' => route('users.edit', $this->process->user),
            ] : null,
            'method_of_creation' => $this->typeCreation,
            'created_at' => $this->process->getAttribute('created_at'),
        ];
    }

    /**
     * Get specific data related to the event
     *
     * @return array
     */
    public function getChanges(): array
    {
        return [
            'id' => $this->process->getAttribute('id')
        ];
    }

    /**
     * Get the Event name with the syntax ‘[Past-test Action] [Object]’
     *
     * @return string
     */
    public function getEventName(): string
    {
        return 'ProcessCreated';
    }
}
