<?php

namespace ProcessMaker\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Permission;

trait HasAuthorization
{
    public function loadPermissions()
    {
        return array_merge(
            $this->loadUserPermissions(),
            $this->loadGroupPermissions()
        );
    }

    public function loadUserPermissions()
    {
        $user = $this;
        $permissions = Cache::remember("user_{$user->id}_permissions", 86400, function () use ($user) {
            return $user->permissions()->pluck('name')->toArray();
        });

        return $this->addCategoryViewPermissions($permissions);
    }

    public function loadGroupPermissions()
    {
        $processedGroups = [];
        $permissions = [];

        foreach ($this->groupMembersFromMemberable as $gm) {
            $group = $gm->group;
            $permissions = $this->loadPermissionOfGroups($group, $permissions, $processedGroups);
            $names = $group->permissions->pluck('name')->toArray();
            $permissions = array_merge($permissions, $names);
        }

        return $this->addCategoryViewPermissions($permissions);
    }

    public function loadPermissionOfGroups(Group $group, array $permissions = [], array $processedGroups = [])
    {
        try {
            // Check if the group was proccessed
            if (in_array($group->id, $processedGroups)) {
                return $permissions;
            }
            // Add the group in the processedList
            $processedGroups[] = $group->id;
            // Load permissions
            $groupPermissions = $group->permissions->pluck('name')->toArray();
            $permissions = array_merge($permissions, $groupPermissions);
            // Review groups
            foreach ($group->groupMembersFromMemberable as $member) {
                $memberGroup = $member->group;
                $permissions = $this->loadPermissionOfGroups(
                    $memberGroup,
                    $permissions,
                    $processedGroups
                );
            }

            return array_unique($permissions);
        } catch (\Exception $e) {
            Log::error('Error loading group permissions: ' . $e->getMessage());

            return $permissions;
        }
    }

    public function hasPermission($permissionString)
    {
        $permissionStrings = $this->loadPermissions();

        return in_array($permissionString, $permissionStrings);
    }

    /**
     * If a user can create or edit a resource,
     * they should be able to view its categories.
     *
     * @param array $permissions
     * @return array $permissions
     */
    private function addCategoryViewPermissions($permissions)
    {
        $addFor = [
            'processes' => 'view-process-categories',
            'scripts' => 'view-script-categories',
            'screens' => 'view-screen-categories',
        ];
        foreach ($addFor as $resource => $categoryPermission) {
            if (
                in_array('create-' . $resource, $permissions) ||
                in_array('edit-' . $resource, $permissions)
            ) {
                if (!in_array($categoryPermission, $permissions)) {
                    $permissions[] = $categoryPermission;
                }
            }
        }

        return $permissions;
    }

    public function giveDirectPermission($permissionNames)
    {
        foreach ((array) $permissionNames as $permissionName) {
            $permissionId = Permission::byName($permissionName)->id;
            $this->permissions()->attach($permissionId);
        }
    }
}
