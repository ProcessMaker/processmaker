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
        } else {
            $permissionStrings = $this->loadPermissions();
        }

        return in_array($permissionString, $permissionStrings);
    }

    public function giveDirectPermission($permission_names)
    {
        foreach ((array) $permission_names as $permission_name) {
            $perm_id = Permission::byGuardName($permission_name)->id;
            PermissionAssignment::create([
                'permission_id' => $perm_id,
                'assignable_type' => User::class,
                'assignable_id' => $this->id,
            ]);
        }
    }

    /**
     * Check permissions of User in process
     *
     * @param Process $process
     * @param $permission
     *
     * @return boolean
     */
    public function hasProcessPermission(Process $process, $permission)
    {
        if ($this->is_administrator) {
            return true;
        }
        $response = $this->hasPermission($permission);
        if ($response) {
            $permission = Permission::byGuardName($permission);
            //Check permission type user
            $response = ProcessPermission::where('permission_id', $permission->id)
                ->where('process_id', $process->id)
                ->where('assignable_id', $this->id)
                ->where('assignable_type', User::class)
                ->exists();
            if (!$response) {
                //check permission type group only in one level
                $response = ProcessPermission::where('permission_id', $permission->id)
                    ->where('process_id', $process->id)
                    ->whereIn('assignable_id', $this->groupMembersFromMemberable()->pluck('group_id')->toArray())
                    ->where('assignable_type', Group::class)
                    ->exists();
            }
        }
        return $response;
    }
}
