<?php

namespace ProcessMaker\Http\Controllers;

use Illuminate\Http\Request;
use ProcessMaker\Models\User;
use ProcessMaker\Models\JsonData;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\PermissionAssignment;

class ProfileController extends Controller
{

    /**
     * edit your profile.
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View
     */
    public function edit()
    {
        $current_user = \Auth::user();
        $states = JsonData::states();
        $timezones = JsonData::timezones();
        $countries = JsonData::countries();
        return view('profile.edit',
            compact('current_user', 'states', 'timezones', 'countries'));
    }

    /**
     * show other users profile
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View
     */
    public function show($id)
    {
        $user = User::findOrFail($id);
        $all_permissions = Permission::all();
        $users_permission_ids = $this->user_permission_ids($user);
        return view('profile.show', compact('user', 'all_permissions', 'users_permission_ids'));
    }
    public function update(Request $request, $id) 
    {
        $user = User::findOrFail($id);
        $users_permission_ids = $this->user_permission_ids($user);
        foreach(Permission::all() as $permission) {
            if ($request->has('permission_'.$permission->id)) {
                if(!in_array($permission->id,$users_permission_ids)){
                    //user needs this permission 
                    PermissionAssignment::create([
                        'permission_id' => $permission->id, 
                        'assignable_type' => User::class, 
                        'assignable_id' => $user->id
                    ]);
                }
            }
        }
    }
    private function user_permission_ids($user) 
    {
        return $user->permissionAssignments()->pluck('permission_id')->toArray();
    }
}

