<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;

class AuthClientCreated implements SecurityLogEventInterface
{
    use Dispatchable;

    private array $data;

    /**
     * Create a new event instance.
     *
     * @param array $data
     *
     * @return void
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Return event data
     */
    public function getData(): array
    {
        return [
            'name' => [
                'label' => $this->data['name'],
                'link' => route('auth-clients.index'),
            ],
            'auth_client_id' => $this->data['id'] ?? '',
            'user_id' => $this->data['user_id'] ?? '',
            'revoked' => $this->data['revoked'] ?? '',
            'provider' => $this->data['provider'] ?? '',
            'redirect' => $this->data['redirect'] ?? '',
            'personal_access_client' => $this->data['personal_access_client'] ?? '',
            'created_at' => $this->data['created_at'] ?? '',
        ];
    }

    /**
     * Return event changes
     */
    public function getChanges(): array
    {
        return [
            'id' => $this->data['id'],
        ];
    }

    /**
     * return event name
     */
    public function getEventName(): string
    {
        return 'AuthClientCreated';
    }
}
