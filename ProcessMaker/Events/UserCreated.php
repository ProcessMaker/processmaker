<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\User;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class UserCreated implements SecurityLogEventInterface
{
    use Dispatchable;
    use FormatSecurityLogChanges;

    private User $user;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $newData)
    {
        $this->user = $newData;
    }

    /**
     * Get specific data related to the event
     *
     * @return array
     */
    public function getData(): array
    {
        return [
            'username' => [
                'label' => $this->user->getAttribute('username'),
                'link' => route('users.edit', $this->user),
            ],
            'firstname' => $this->user->getAttribute('firstname'),
            'lastname' => $this->user->getAttribute('lastname'),
            'title' => $this->user->getAttribute('title'),
            'status' => $this->user->getAttribute('status'),
            'email' => $this->user->getAttribute('email'),
            'created_at' => $this->user->getAttribute('created_at'),
        ];
    }

    /**
     * Get specific changes without format related to the event
     *
     * @return array
     */
    public function getChanges(): array
    {
        return [
            $this->user->getAttributes(),
        ];
    }

    /**
     * Get the Event name with the syntax ‘[Past-test Action] [Object]’
     *
     * @return string
     */
    public function getEventName(): string
    {
        return 'UserCreated';
    }
}
