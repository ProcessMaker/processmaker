<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Models\User;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\PermissionAssignment;


//@TODO annotate permissions
class PermissionController extends Controller
{

    public function update(Request $request) 
    {
        // find the user
        $user = User::findOrFail($request->input('user_id'));
        $selected_permission_ids = $request->input('permission_ids');

        // assign the users permissions ids
        $users_permission_ids = $this->user_permission_ids($user);
        foreach(Permission::all() as $permission) {
            //if the request has the ids present
            if (in_array($permission->id, $selected_permission_ids)) {
                // and the id is not in the array
                if(!in_array($permission->id,$users_permission_ids)){
                    // the user needs to add permissions 
                    PermissionAssignment::create([
                        'permission_id' => $permission->id, 
                        'assignable_type' => User::class, 
                        'assignable_id' => $user->id
                    ]);
                }
            } else { 
                if(in_array($permission->id,$users_permission_ids)){
                    //user needs to delete this permission 
                    PermissionAssignment::where([
                        'permission_id' => $permission->id, 
                        'assignable_type' => User::class, 
                        'assignable_id' => $user->id
                    ])->delete();
                }
            }
        }
    }
    private function user_permission_ids($user) 
    {
        return $user->permissionAssignments()->pluck('permission_id')->toArray();
    }
}