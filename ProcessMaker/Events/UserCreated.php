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
        // Only specific changes to show
        return [
            'username' => [
                'label' => $this->user->getAttribute('username'),
                'link' => route('users.edit', $this->user),
            ],
            'first_name' => $this->user->getAttribute('first_name'),
            'last_name' => $this->user->getAttribute('last_name'),
            'title' => $this->user->getAttribute('title'),
            'status' => $this->user->getAttribute('status'),
            'email' => $this->user->getAttribute('email'),
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
