<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;

class ScriptExecutorUpdated implements SecurityLogEventInterface
{
    use Dispatchable;

    public $data;
    public $changes;
    public $original_values;
    public $changed_values;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(array $original_values, array $changed_values)
    {
        $this->original_values = $original_values;
        $this->changed_values = $changed_values;
        $this->buildData();
        $this->changes = $changed_values;
    }

    /**
     * Building the data
     */
    public function buildData() {
        $this->data = [
            'script executor id' => $this->original_values['id']
        ];

        if ($this->original_values['title'] !== $this->changed_values['title']) {
            $this->data['- title'] = $this->original_values['title'];
            $this->data['+ title'] = $this->changed_values['title'];
        }

        if ($this->original_values['description'] !== $this->changed_values['description']) {
            $this->data['- description'] = $this->original_values['description'];
            $this->data['+ description'] = $this->changed_values['description'];
        }

        if ($this->original_values['language'] !== $this->changed_values['language']) {
            $this->data['- language'] = $this->original_values['language'];
            $this->data['+ language'] = $this->changed_values['language'];
        }

        if ($this->original_values['config'] !== $this->changed_values['config']) {
            $this->data['- config'] = $this->original_values['config'];
            $this->data['+ config'] = $this->changed_values['config'];
        }
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
