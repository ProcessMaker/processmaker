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

    /**
     * Update a user
     *
     * @param User $user
     * @param Request $request
     *
     * @return Response
     *
     *     @OA\Put(
     *     path="/users/userId",
     *     summary="Update a user",
     *     operationId="updateUsers",
     *     tags={"Users"},
     *     @OA\Parameter(
     *         description="ID of user to return",
     *         in="path",
     *         name="user_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/usersEditable")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/users")
     *     ),
     * )
     */
    public function update(Request $request) 
    {
        // find the user
        $user = User::findOrFail($request->input('user_id'));
        $selected_permission_ids = $request->input('permission_ids');

        $user->is_administrator = $request->has('is_administrator')
                                ? $request->input('is_administrator')
                                : false;
        $user->update();

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

        return response([], 204);
    }
    private function user_permission_ids($user) 
    {
        return $user->permissionAssignments()->pluck('permission_id')->toArray();
    }
}