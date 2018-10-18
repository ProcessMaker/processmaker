<?php
namespace ProcessMaker\Traits;

use ProcessMaker\Models\User;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\PermissionAssignment;
use Illuminate\Support\Facades\Cache;

trait HasAuthorization
{
    public function loadPermissions()
    {
        $permissions = [];
        foreach ($this->groupMembersFromMemberable as $gm) {
            $group = $gm->group;
            $permissions =
                array_merge($permissions, $group->permissions());
        }
        foreach ($this->permissionAssignments as $pa) {
            $permissions[] = $pa->permission;
        }
        $permissionStrings = array_map(
            function($p) { return $p->guard_name; },
            $permissions
        );
        return $permissionStrings;
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
        }

        return in_array($permissionString, $permissionStrings);
    }

    public function giveDirectPermission($permission_names)
    {
        foreach ((array) $permission_names as $permission_name) {
            $perm_uuid = Permission::byGuardName($permission_name)->uuid;
            PermissionAssignment::create([
                'permission_uuid' => $perm_uuid,
                'assignable_type' => User::class,
                'assignable_uuid' => $this->uuid,
            ]);
        }
    }
}