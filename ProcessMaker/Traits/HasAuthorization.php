<?php

namespace ProcessMaker\Traits;

use Illuminate\Support\Facades\Auth;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessPermission;
use ProcessMaker\Models\User;

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
        $permissions = $this->permissions->pluck('name')->toArray();

        return $this->addCategoryViewPermissions($permissions);
    }

    public function loadGroupPermissions()
    {
        $permissions = [];

        foreach ($this->groupMembersFromMemberable as $gm) {
            $group = $gm->group;
            $permissions = $this->loadPermissionOfGroups($group, $permissions);
            $names = $group->permissions->pluck('name')->toArray();
            $permissions = array_merge($permissions, $names);
        }

        return $this->addCategoryViewPermissions($permissions);
    }

    public function loadPermissionOfGroups(Group $group, array $permissions = [])
    {
        foreach ($group->groupMembersFromMemberable as $member) {
            $group = $member->group;
            $permissions = $this->loadPermissionOfGroups($group, $permissions);
            $permissions = array_merge($permissions, $group->permissions->pluck('name')->toArray());
        }

        return $permissions;
    }

    public function hasPermission($permissionString)
    {
        if (\Auth::user() == $this) {
            if (session('permissions')) {
                $permissionStrings = session('permissions');
            } else {
                $permissionStrings = $this->loadPermissions();
                session(['permissions' => $permissionStrings]);
            }
        } else {
            $permissionStrings = $this->loadPermissions();
        }

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
