<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\Screen;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class ScreenUpdated implements SecurityLogEventInterface
{
    use Dispatchable, FormatSecurityLogChanges;

    
    private Screen $screen;
    private $changes;
    private $original;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Screen $screen, $changes, $original)
    {  
        $this->screen = $screen;
        $this->changes = $changes;
        $this->original = $original;
    }

    /**
     * Get specific data related to the event
     *
     * @return array
     */
    public function getData(): array
    {
        return array_merge([
            'last_modified' => $this->screen->getAttribute('updated_at'),
        ], $this->formatChanges($this->changes, $this->original));
    }

    /**
     * Get the Event name with the syntax ‘[Past-test Action] [Object]’
     *
     * @return string
     */
    public function getEventName(): string
    {
        return 'ScreenUpdated';
    }

    /**
     * Get specific changes without format related to the event
     *
     * @return array
     */
    public function getChanges(): array
    {
        return $this->changes;
    }
}
