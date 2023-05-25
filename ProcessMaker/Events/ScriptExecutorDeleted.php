<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;

class ScriptExecutorDeleted implements SecurityLogEventInterface
{
    use Dispatchable;

    private array $data;
    private array $changes;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(array $deleted_values)
    {
        $this->changes = $deleted_values;
        $this->data = ['script_executor_id' => $deleted_values['id']];
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
        return 'ScriptExecutorDeleted';
    }
}
