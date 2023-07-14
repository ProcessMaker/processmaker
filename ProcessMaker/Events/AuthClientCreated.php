<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;

class AuthClientCreated implements SecurityLogEventInterface
{
    use Dispatchable;

    private array $data;

    private array $changes;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(array $created_values)
    {
        $this->data = [
            'auth_client_id' => $created_values['id'],
            'name' => $created_values['name'],
            'user_id' => $created_values['user_id'],
            'revoked' => $created_values['revoked'],
            'provider' => $created_values['provider'],
            'redirect' => $created_values['redirect'],
            'password_client' => $created_values['password_client'],
            'personal_access_client' => $created_values['personal_access_client'],
        ];
        $this->changes = $created_values;
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
        return 'AuthClientCreated';
    }
}
