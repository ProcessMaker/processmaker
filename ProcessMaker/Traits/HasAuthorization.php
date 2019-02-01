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
        return $this->permissions->pluck('name')->toArray();
    }
    
    public function loadGroupPermissions()
    {
        $permissions = [];
        
        foreach ($this->groupMembersFromMemberable as $gm) {
            $group = $gm->group;
            $names = $group->permissions->pluck('name')->toArray();
            $permissions = array_merge($permissions, $names);
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

    public function giveDirectPermission($permissionNames)
    {
        foreach ((array) $permissionNames as $permissionName) {
            $permissionId = Permission::byName($permissionName)->id;            
            $this->permissions()->attach($permissionId);
        }
    }

}