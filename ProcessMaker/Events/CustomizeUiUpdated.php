<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class CustomizeUiUpdated implements SecurityLogEventInterface
{
    use Dispatchable, FormatSecurityLogChanges;

    private array $data;
    private array $changes;
    private array $original;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(array $original, array $changes)
    {
        $changes = array_diff_assoc($changes, $original);
        $original = array_intersect_key($original, $changes);
        dd($original, $changes);
        $this->original = $original;
        $this->changes = $changes;
        $this->buildData();
    }

    /**
     * Building the data
     */
    public function buildData() {
        $this->data = $this->formatChanges($this->changes, $this->original);
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
        return 'CustomizeUiUpdated';
    }
}
