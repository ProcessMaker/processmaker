<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;

class ScriptExecutorCreated implements SecurityLogEventInterface
{
    use Dispatchable;

    private array $data;

    /**
     * Create a new event instance.
     *
     * @param array $data
     *
     * @return void
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Return event data
     */
    public function getData(): array
    {
        return [
            'name' => [
                'label' => $this->data['title'],
                'link' => route('script-executors.index'),
            ],
            'script_executor_id' => $this->data['id'] ?? '',
            'description' => isset($this->data['description']) ? $this->data['description'] : '',
            'language' => $this->data['language'] ?? '',
            'config' => $this->data['config'] ?? '',
            'created_at' => $this->data['created_at'] ?? '',
        ];
    }

    /**
     * Return event changes
     */
    public function getChanges(): array
    {
        return [
            'script_executor_id' => $this->data['id'] ?? '',
        ];
    }

    /**
     * return event name
     */
    public function getEventName(): string
    {
        return 'ScriptExecutorCreated';
    }
}
