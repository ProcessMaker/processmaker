<?php

namespace ProcessMaker\Events;

use Exception;
use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Helpers\ArrayHelper;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\User;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class PermissionUpdated implements SecurityLogEventInterface
{
    use Dispatchable;
    use FormatSecurityLogChanges;

    private array $changedPermissions;

    private array $originalPermissions;

    private bool $permissionType;

    private ?string $userId;

    private ?string $groupId;

    private array $arrayPermissions = [];

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(
        array $changedPermissions,
        array $originalPermissions,
        bool $permissionType,
        ?string $userId,
        ?string $groupId
    ) {
        $this->changedPermissions = $changedPermissions;
        $this->originalPermissions = $originalPermissions;
        $this->permissionType = $permissionType;
        $this->userId = $userId;
        $this->groupId = $groupId;

        $this->arrayPermissions = ArrayHelper::getArrayDifferencesWithFormat(
            $this->changedPermissions,
            $this->originalPermissions
        );
    }

    /**
     * Get specific data related to the event
     *
     * @return array
     */
    public function getData(): array
    {
        if ($this->userId) {
            return $this->getUserData();
        } elseif ($this->groupId) {
            return $this->getGroupData();
        } else {
            throw new Exception('No user or group id was provided');
        }
    }

    /**
     * Get specific data related to the user
     *
     * @return array
     */
    public function getUserData(): array
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
            foreach ($this->arrayPermissions as $key => $value) {
                $this->arrayPermissions = ArrayHelper::replaceKeyInArray(
                    $this->arrayPermissions,
                    $key,
                    substr($key, 0, 1) . ' Permission' . substr($key, 2)
                );
            }

            return array_merge([
                'name' => [
                    'label' => $userData->getAttribute('username'),
                    'link' => route('users.edit', $this->userId) . '#nav-profile',
                ],
                'last_modified' => $userData->getAttribute('updated_at'),
            ], $this->arrayPermissions);
        }
    }

    /**
     * Get specific data related to the group
     *
     * @return array
     */
    public function getGroupData(): array
    {
        //get Group profile data
        $group = Group::find($this->groupId);
        foreach ($this->arrayPermissions as $key => $value) {
            $this->arrayPermissions = ArrayHelper::replaceKeyInArray(
                $this->arrayPermissions,
                $key,
                substr($key, 0, 1) . ' Permission' . substr($key, 2)
            );
        }

        return array_merge([
            'name' => [
                'label' => $group->name,
                'link' => route('groups.edit', $this->groupId) . '#nav-permissions',
            ],
            'last_modified' => now(),
        ], $this->arrayPermissions);
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
