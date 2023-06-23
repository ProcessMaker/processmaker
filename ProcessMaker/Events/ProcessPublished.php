<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\Process;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class ProcessPublished implements SecurityLogEventInterface
{
    use Dispatchable;
    use FormatSecurityLogChanges;

    // Currently is not required to register the following columns (related to the diagram)
    public const REMOVE_KEYS = [
        'bpmn',
        'svg',
        'start_events',
        'self_service_tasks',
        'signal_events',
        'conditional_events',
        'properties',
    ];

    private Process $process;

    private array $changes;

    private array $original;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Process $data, array $changes, array $original)
    {
        $this->process = $data;
        $this->changes = array_diff_key($changes, array_flip($this::REMOVE_KEYS));
        $this->original = array_diff_key($original, array_flip($this::REMOVE_KEYS));
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
                'label' => $this->process->getAttribute('name'),
                'link' => route('modeler.show', $this->process),
            ],
            'category' => $this->process->category ? $this->process->category->name : null,
            'action' => $this->process->getAttribute('status'),
            'updated_at' => $this->process->getAttribute('updated_at'),
        ], $this->formatChanges($this->changes, $this->original));
    }

    /**
     * Get specific data related to the event
     *
     * @return array
     */
    public function getChanges(): array
    {
        return array_merge(
            ['id' => $this->process->getAttribute('id')],
            $this->getData()
        );
    }

    /**
     * Get the Event name with the syntax ‘[Past-test Action] [Object]’
     *
     * @return string
     */
    public function getEventName(): string
    {
        return 'ProcessUpdated';
    }
}
