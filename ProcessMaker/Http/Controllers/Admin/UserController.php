<?php

namespace ProcessMaker\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\User;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\PermissionAssignment;

class UserController extends Controller
{
    /**
     * Get the list of users.
     *
     * @return Factory|View
     */
    public function index()
    {
        //load groups
        $groups = $this->getAllGroups();
        return view('admin.users.index', compact(['groups']));
    }

    /**
     * @param User $user
     *
     * @return Factory|View
     */
    public function edit(User $user)
    {
        //include memberships
        $user->memberships = $user->groupMembersFromMemberable()->get();
        $groups = $this->getAllGroups();
        $permission_ids = $user->permissionAssignments()->pluck('permission_id')->toArray();
        $all_permissions = Permission::all();
        $users_permission_ids = $this->user_permission_ids($user);
        return view('admin.users.edit', compact(['user', 'groups', 'all_permissions', 'users_permission_ids', 'permission_ids']));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function show(User $user)
    {
        return view('admin.users.show', compact('user'));
    }

    /**
     * Get all groups actives
     *
     * @return mixed
     */
    private function getAllGroups()
    {
        return Group::where('status', 'ACTIVE')->get();
    }

    private function user_permission_ids($user) 
    {
        return $user->permissionAssignments()->pluck('permission_id')->toArray();
    }
}
