<?php

namespace ProcessMaker\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class AuthClientUpdated implements SecurityLogEventInterface
{
    use Dispatchable;
    use FormatSecurityLogChanges;

    private array $original;
    private array $data;
    private array $changes;
    private string $clientId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(string $clientId, array $originalValues, array $changedValues)
    {
        $this->original = $originalValues;
        $this->changes = $changedValues;
        $this->clientId = $clientId;
    }

    /**
     * Return event data
     */
    public function getData(): array
    {
        return array_merge([
            'auth_client_id' => $this->clientId,
            'last_modified' => $this->changes['updated_at'] ?? Carbon::now()
        ], $this->formatChanges($this->changes, $this->original));
    }

    /**
     * Return event changes
     */
    public function getChanges(): array
    {
        return [
            'auth_client_id' => $this->clientId,
        ];
    }

    /**
     * return event name
     */
    public function getEventName(): string
    {
        return 'AuthClientUpdated';
    }
}
