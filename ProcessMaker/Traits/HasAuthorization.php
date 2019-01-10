<?php
namespace ProcessMaker\Traits;

use Illuminate\Support\Facades\Auth;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\PermissionAssignment;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessPermission;
use ProcessMaker\Models\User;

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
            function($p) { return $p->name; },
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
        } else {
            $permissionStrings = $this->loadPermissions();
        }

        return in_array($permissionString, $permissionStrings);
    }

    public function giveDirectPermission($permission_names)
    {
        foreach ((array) $permission_names as $permission_name) {
            $perm_id = Permission::byName($permission_name)->id;
            PermissionAssignment::create([
                'permission_id' => $perm_id,
                'assignable_type' => User::class,
                'assignable_id' => $this->id,
            ]);
        }
    }

}