<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Helpers\ArrayHelper;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
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

    public const REMOVE_KEYS_AUX = [
        'tmp_process_category_id',
        'process_category_id',
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

        // Get category name
        $this->original['process_category'] = isset($original['process_category_id'])
        ? ProcessCategory::getNamesByIds($this->original['process_category_id']) : '';
        unset($this->original['process_category_id']);
        $this->changes['process_category'] = isset($changes['process_category_id'])
        ? ProcessCategory::getNamesByIds($this->changes['tmp_process_category_id']) : '';
        $this->changes = array_diff_key($this->changes, array_flip($this::REMOVE_KEYS_AUX));
        if (empty($this->changes['process_category'])) {
            unset($this->changes['process_category']);
        }
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
            'last_modified' => $this->process->getAttribute('updated_at'),
        ], ArrayHelper::getArrayDifferencesWithFormat($this->changes, $this->original));
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
        ];
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

    /**
     * Get the Process
     *
     * @return Process
     */
    public function getProcess(): Process
    {
        return $this->process;
    }
}
