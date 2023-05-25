<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class ScriptExecutorUpdated implements SecurityLogEventInterface
{
    use Dispatchable, FormatSecurityLogChanges;

    private array $data;
    private array $changes;
    private array $original;
    private int $scriptId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(int $scriptId, array $original_values, array $changed_values)
    {
        $this->original = array_intersect_key($original_values, $changed_values);
        $this->changes = $changed_values;
        $this->scriptId = $scriptId;
        $this->buildData();
    }

    /**
     * Building the data
     */
    public function buildData() {
        $this->data = array_merge([
            'script_executor_id' => $this->scriptId
        ], $this->formatChanges($this->changes, $this->original));
    }
    
    /**
     * Return event data 
     */
    public function getData(): array
    {
        return $this->data;
    }
    
    /**
     * Return event changes 
     */
    public function getChanges(): array
    {
        return $this->changes;
    }

    /**
     * return event name
     */
    public function getEventName(): string
    {
        return 'ScriptExecutorUpdated';
    }
}
