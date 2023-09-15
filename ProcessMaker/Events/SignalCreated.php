<?php

namespace ProcessMaker\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;

class SignalCreated implements SecurityLogEventInterface
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
     */
    public function getData(): array
    {
        return [
            'name' => [
                'label' => $this->signal['name'],
                'link' => route('signals.edit', ['signalId' => $this->signal['id']]),
            ],
            'detail' => $this->signal['detail'] ?? '',
            'created_at' => Carbon::now(),
        ];
    }

    /**
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
        return 'SignalCreated';
    }
}
