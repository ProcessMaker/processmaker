<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;

class ScriptExecutorCreated implements SecurityLogEventInterface
{
    use Dispatchable;

    public $data;
    public $changes;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(array $created_values)
    {
        $this->changes = $created_values;
        $this->data = [
            'script executor id' => $created_values['id'],
            'title' => $created_values['title'],
            'description' => $created_values['description'],
            'language' => $created_values['language'],
            'config' => $created_values['config']
        ];
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
        return 'ScriptExecutorCreated';
    }
}
