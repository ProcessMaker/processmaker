<?php

namespace ProcessMaker\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;

class AuthClientDeleted implements SecurityLogEventInterface
{
    use Dispatchable;

    private array $data;

    private array $changes;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(array $values)
    {
        $this->changes = $values;
    }

    /**
     * Return event data
     */
    public function getData(): array
    {
        return [
            'auth_client_id' => $this->changes['id'] ?? 0,
            'name' => $this->changes['name'] ?? 0,
            'deleted_at' => Carbon::now()
        ];
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
        return 'AuthClientDeleted';
    }
}
