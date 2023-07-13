<?php

namespace ProcessMaker\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;

class SignalDeleted implements SecurityLogEventInterface
{
    use Dispatchable;

    private array $signal;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(array $data)
    {
        $this->signal = $data;
    }

    /**
     * Return event data
     * Return event data
     */
    public function getData(): array
    {
        return [
            'name' => $this->signal['name'] ?? '',
            'deleted_at' => Carbon::now(),
        ];
    }

    /**
     * Return event changes
     * Return event changes
     */
    public function getChanges(): array
    {
        return [
            'id' => $this->signal['id'] ?? '',
        ];
    }

    /**
     * return event name
     */
    public function getEventName(): string
    {
        return 'SignalDeleted';
    }
}
