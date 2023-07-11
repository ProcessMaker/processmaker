<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\User;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class PermissionUpdated implements SecurityLogEventInterface
{
    use Dispatchable;
    use FormatSecurityLogChanges;

    private array $changedPermissions;

    private array $originalPermissions;

    private bool $permissionType;

    private string $userId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(
        array $changedPermissions,
        array $originalPermissions,
        bool $permissionType,
        string $userId
    ) {
        $this->changedPermissions = $changedPermissions;
        $this->originalPermissions = $originalPermissions;
        $this->permissionType = $permissionType;
        $this->userId = $userId;
    }

    /**
     * Get specific data related to the event
     *
     * @return array
     */
    public function getData(): array
    {
        //get User profile data
        $userData = User::find($this->userId);
        if ($this->permissionType == true) {
            return [
                'name' => [
                    'label' => $userData->getAttribute('username'),
                    'link' => route('users.edit', $this->userId) . '#nav-profile',
                ],
                '+ permission_names' => 'Super Admin - All Permissions',
            ];
        } else {
            return array_merge([
                'name' => [
                    'label' => $userData->getAttribute('username'),
                    'link' => route('users.edit', $this->userId) . '#nav-profile',
                ],
            ], $this->formatChanges($this->changedPermissions, $this->originalPermissions));
        }
    }

    /**
     * Get specific changes without format related to the event
     *
     * @return array
     */
    public function getChanges(): array
    {
        return $this->changedPermissions;
    }

    /**
     * Get the Event name with the syntax ‘[Past-test Action] [Object]’
     *
     * @return string
     */
    public function getEventName(): string
    {
        return 'PermissionUpdated';
    }
}
