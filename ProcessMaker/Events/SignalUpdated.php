<?php

namespace ProcessMaker\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Helpers\ArrayHelper;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class SignalUpdated implements SecurityLogEventInterface
{
    use Dispatchable;
    use FormatSecurityLogChanges;

    private array $changes;
    private array $original;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(array $changes, array $original)
    {
        $this->changes = $changes;
        $this->original = $original;
    }

    /**
     * Return event data
     */
    public function getData(): array
    {
        return array_merge([
            'name' => [
                'label' => $this->changes['name'],
                'link' => route('signals.edit', ['signalId' => $this->changes['id']]),
            ],
            'last_modified' => Carbon::now()
        ], ArrayHelper::getArrayDifferencesWithFormat($this->changes, $this->original));
    }

    /**
     * Return event changes
     */
    public function getChanges(): array
    {
        return [
            'id' => $this->changes['id'] ?? '',
        ];
    }

    /**
     * return event name
     */
    public function getEventName(): string
    {
        return 'SignalUpdated';
    }
}
