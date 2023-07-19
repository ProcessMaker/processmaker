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
    private array $changes;
    private string $clientId;
    private string $clientName;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(string $clientId, array $originalValues, array $changedValues, $name = '')
    {
        $this->original = $originalValues;
        $this->changes = $changedValues;
        $this->clientId = $clientId;
        $this->clientName = $name;
    }

    /**
     * Return event data
     */
    public function getData(): array
    {
        return array_merge([
            'name' => [
                'label' => $this->changes['name'] ?? $this->clientName,
                'link' => route('auth-clients.index'),
            ],
            'last_modified' => $this->changes['updated_at'] ?? Carbon::now(),
        ], $this->formatChanges($this->changes, $this->original));
    }

    /**
     * Return event changes
     */
    public function getChanges(): array
    {
        return [
            'id' => $this->clientId,
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
