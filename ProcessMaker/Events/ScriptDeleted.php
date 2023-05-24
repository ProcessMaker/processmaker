<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\Script;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class ScriptDeleted implements SecurityLogEventInterface
{
    use Dispatchable, FormatSecurityLogChanges;

    private Script $script;

    /**
     * Create a new event instance.
     *
     * @param Script $script
     */
    public function __construct(Script $script)
    {
        $this->script = $script;
    }

    public function getChanges(): array
    {
        return [
            'script_id' => $this->script->id
        ];
    }

    public function getData(): array
    {
        return [
            'Name' => $this->script->getAttribute('title'),
        ];
    }

    public function getEventName(): string
    {
        return 'ScriptDeleted';
    }
}
