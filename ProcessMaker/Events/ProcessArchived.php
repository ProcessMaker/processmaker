<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\Process;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class ProcessArchived implements SecurityLogEventInterface
{
    use Dispatchable;
    use FormatSecurityLogChanges;

    private Process $process;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Process $data)
    {
        $this->process = $data;
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
            'action' => $this->process->getAttribute('status'),
            'updated_at' => $this->process->getAttribute('updated_at'),
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
            'id' => $this->process->getAttribute('id'),
            'status' => $this->process->getAttribute('status'),
        ];
    }

    /**
     * Get the Event name with the syntax ‘[Past-test Action] [Object]’
     *
     * @return string
     */
    public function getEventName(): string
    {
        return 'ProcessArchived';
    }
}
