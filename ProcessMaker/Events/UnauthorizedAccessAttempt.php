<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;

class UnauthorizedAccessAttempt implements SecurityLogEventInterface
{
    use Dispatchable;

    private array $data;

    /**
     * Create a new event instance.
     *
     */
    public function __construct()
    {
        $this->data = [
            'url' => [
                'link' => url()->current(),
                'label' => url()->current()
            ]
        ];
    }

    public function getChanges(): array
    {
        return [];
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getEventName(): string
    {
        return 'UnauthorizedAccessAttempt';
    }
}
