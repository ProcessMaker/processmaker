<?php

namespace ProcessMaker\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\Script;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class ScriptDeleted implements SecurityLogEventInterface
{
    use Dispatchable;
    use FormatSecurityLogChanges;

    private Script $script;
    public string $userDelete;

    /**
     * Create a new event instance.
     *
     * @param Script $script
     */
    public function __construct(Script $script)
    {
        $this->script = $script;
    }

    /**
     * Get specific changes without format related to the event
     *
     * @return array
     */
    public function getChanges(): array
    {
        return [
            'script_id' => $this->script->id,
        ];
    }

    /**
     * Get specific data related to the event
     *
     * @return array
     */
    public function getData(): array
    {
        return [
            'name' => $this->script->getAttribute('title'),
            'deleted_at' => Carbon::now()
        ];
    }

    public function getEventName(): string
    {
        return 'ScriptDeleted';
    }
}
