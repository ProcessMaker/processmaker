<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Helpers\ArrayHelper;
use ProcessMaker\Models\User;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class UserUpdated implements SecurityLogEventInterface
{
    use Dispatchable;
    use FormatSecurityLogChanges;

    private User $user;
    private array $changes;
    private array $original;
    public const REMOVE_KEYS = [
        'meta',
        'schedule'
    ];
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, array $changes, array $original)
    {
        $this->user = $user;
        $this->changes = array_diff_key($changes, array_flip($this::REMOVE_KEYS));
        $this->original = array_diff_key($original, array_flip($this::REMOVE_KEYS));
    }

    /**
     * Get specific data related to the event
     *
     * @return array
     */
    public function getData(): array
    {
        return array_merge([
            'name' => [
                'label' => $this->user->getAttribute('username'),
                'link' => route('users.edit', $this->user->getAttribute('id')) . '#nav-home',
            ],
            'username' => $this->user->getAttribute('username'),
        ], ArrayHelper::getArrayDifferencesWithFormat($this->changes, $this->original));
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
