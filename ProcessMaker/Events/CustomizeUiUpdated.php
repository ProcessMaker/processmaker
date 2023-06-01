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
    public function __construct(array $original, array $changes, string $updatedAt)
    {
        $changes = array_diff_assoc($changes, $original);
        $original = array_intersect_key($original, $changes);
        $this->original = $original;
        $this->changes = $changes;
        $this->changes['update_at'] = $updatedAt;
        $this->buildData();
    }

    /**
     * Building the data
     */
    public function buildData() 
    {
        if (isset($this->changes['variables'])) {
            $variables_changes = [];
            $variables_original = [];
            foreach ((array)json_decode($this->changes['variables'], true) as $variable) {
                $variables_changes[$variable['title']] = $variable['value'];
            }
            foreach ((array)json_decode($this->original['variables'], true) as $variable) {
                $variables_original[$variable['title']] = $variable['value'];
            }
            $variables_changes = array_diff($variables_changes, $variables_original);
            $variables_original = array_intersect_key($variables_original, $variables_changes);
            
            $this->changes['variables'] = $variables_changes;
            $this->original['variables'] = $variables_original;
        }
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
