<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Models\User;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Permission;

class PermissionController extends Controller
{

    /**
     * Update permissions
     *
     * @param Request $request
     *
     * @return Response
     *
     *     @OA\Put(
     *     path="/permissions",
     *     summary="Update the permissions of an user",
     *     tags={"Permissions"},
     *
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(
     *          @OA\Property(
     *              property="user_id",
     *              type="integer",
     *              description="Id of the user whose permissions are configured"),
     *          @OA\Property(
     *              property="group_id",
     *              type="integer",
     *              description="Id of the group whose permissions are configured"),
     *          @OA\Property(
     *              property="permissions_names",
     *              type="array",
     *              collectionFormat="multi",
     *              @OA\Items (type="integer"))
     *       )
     *     ),
     *
     *     @OA\Response(
     *         response=204,
     *         description="success",
     *     ),
     * )
     */
    public function update(Request $request) 
    {
        //Obtain the requested user or group
        if ($request->input('user_id')) {
            $entity = User::findOrFail($request->input('user_id'));
        } elseif ($request->input('group_id')) {
            $entity = Group::findOrFail($request->input('group_id'));
        }
        
        //Obtain the requested permission names for that entity
        $requestPermissions = $request->input('permission_names');    

        //Convert permission names into a collection of Permission models
        $permissions = Permission::whereIn('name', $requestPermissions)->get();

        //Sync the entity's permissions with the database
        $entity->permissions()->sync($permissions->pluck('id')->toArray());

        return response([], 204);
    }

}