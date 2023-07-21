<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Laravel\Passport\Token;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\User;

class TokenCreated implements SecurityLogEventInterface
{
    use Dispatchable;

    private Token $data;
    private User $user;
    private string $name;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Token $token, User $user, string $name = '')
    {
        $this->data = $token;
        $this->user = $user;
        $this->name = $name;
    }

    /**
     * Return event data
     */
    public function getData(): array
    {
        return [
            'name' => $this->name,
            'id' => substr($this->data->getAttribute('id'), 0, 5),
            'user' => $this->user->username,
            'created_at' => $this->data->getAttribute('created_at'),
        ];
    }

    /**
     * Return event changes
     */
    public function getChanges(): array
    {
        return [
            'client_id' => $this->data->getAttribute('client_id'),
        ];
    }

    /**
     * return event name
     */
    public function getEventName(): string
    {
        return 'TokenCreated';
    }
}
