<?php

namespace ProcessMaker\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\User;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class UserDeleted implements SecurityLogEventInterface
{
    use Dispatchable;
    use FormatSecurityLogChanges;

    private User $user;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $dataDeleted)
    {
        $this->user = $dataDeleted;
    }

    /**
     * Get specific data related to the event
     *
     * @return array
     */
    public function getData(): array
    {
        return [
            'firstname' => $this->user->getAttribute('firstname'),
            'lastname' => $this->user->getAttribute('lastname'),
            'deleted_at' => Carbon::now()
        ];
    }

    /**
     * Get specific changes without format related to the event
     *
     * @return array
     */
    public function getChanges(): array
    {
        return $this->user->getAttributes();
    }

    /**
     * Get the Event name with the syntax ‘[Past-test Action] [Object]’
     *
     * @return string
     */
    public function getEventName(): string
    {
        return 'UserDeleted';
    }
}
