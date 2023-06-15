<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\User;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class UserUpdated implements SecurityLogEventInterface
{
    use Dispatchable;
    use FormatSecurityLogChanges;

    private User $user;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Get specific data related to the event
     *
     * @return array
     */
    public function getData(): array
    {
        $oldData = array_diff_assoc($this->user->getOriginal(), $this->user->getAttributes());
        $newData = array_diff_assoc($this->user->getAttributes(), $this->user->getOriginal());

        return array_merge([
            'name' => [
                'label' => $this->user->getAttribute('username'),
                'link' => route('users.edit', $this->user->getAttribute('id')) . '#nav-home',
            ],
            'username' => $this->user->getAttribute('username'),
        ], $this->formatChanges($newData, $oldData));
    }

    /**
     * Get specific changes without format related to the event
     *
     * @return array
     */
    public function getChanges(): array
    {
        return [
            'id' => $this->user->getAttribute('id'),
            'username' => $this->user->getAttribute('username'),
            'status' => $this->user->getAttribute('status'),
        ];
    }

    /**
     * Get the Event name with the syntax ‘[Past-test Action] [Object]’
     *
     * @return string
     */
    public function getEventName(): string
    {
        return 'UserUpdated';
    }
}
