<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class AuthClientUpdated implements SecurityLogEventInterface
{
    use Dispatchable, FormatSecurityLogChanges;

    private array $original;

    private array $data;

    private array $changes;

    private string $clientId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(string $clientId, array $original_values, array $changed_values)
    {
        $this->original = $original_values;
        $this->changes = $changed_values;
        $this->clientId = $clientId;
        $this->buildData();
    }

    /**
     * Building the data
     */
    public function buildData()
    {
        $this->data = array_merge([
            'auth_client_id' => $this->clientId,
        ], $this->formatChanges($this->changes, $this->original));
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
        return 'AuthClientUpdated';
    }
}
