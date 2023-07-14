<?php

namespace ProcessMaker\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class ScriptExecutorUpdated implements SecurityLogEventInterface
{
    use Dispatchable;
    use FormatSecurityLogChanges;

    private array $data;

    private array $changes;

    private array $original;

    private int $scriptId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(int $scriptId, array $originalValues, array $changedValues)
    {
        $this->original = array_intersect_key($originalValues, $changedValues);
        $this->changes = $changedValues;
        $this->scriptId = $scriptId;
    }

    /**
     * Return event data
     * Return event data
     */
    public function getData(): array
    {
        return array_merge([
            'script_executor_id' => $this->scriptId,
            'last_modified' => $this->changes['updated_at'] ?? Carbon::now(),
        ], $this->formatChanges($this->changes, $this->original));
    }

    /**
     * Return event changes
     * Return event changes
     */
    public function getChanges(): array
    {
        return [
            'script_executor_id' => $this->scriptId,
        ];
    }

    /**
     * return event name
     */
    public function getEventName(): string
    {
        return 'ScriptExecutorUpdated';
    }
}
