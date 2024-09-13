<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use ProcessMaker\Events\PermissionChanged;
use ProcessMaker\Events\PermissionUpdated;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\User;

class PermissionController extends Controller
{
    /**
     * A whitelist of attributes that should not be
     * sanitized by our SanitizeInput middleware.
     *
     * @var array
     */
    public $doNotSanitize = [
        //
    ];

    /**
     * List permissions
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $all_permissions = Permission::all();

        return $all_permissions->sortBy('title')->groupBy('group')->sortKeys();
    }

    /**
     * Update permissions
     *
     * @param Request $request
     *
     * @return Response
     *
     *     @OA\Put(
     *     path="/permissions",
     *     summary="Update the permissions of a user",
     *     tags={"Permissions"},
     *
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(
     *          @OA\Property(
     *              property="user_id",
     *              type="integer",
     *              description="ID of the user whose permissions are configured"),
     *          @OA\Property(
     *              property="group_id",
     *              type="integer",
     *              description="ID of the group whose permissions are configured"),
     *          @OA\Property(
     *              property="is_administrator",
     *              type="boolean",
     *              default=false,
     *              description="Whether the user should have Super Admin privileges"),
     *          @OA\Property(
     *              property="permission_names",
     *              type="array",
     *              @OA\Items (type="string"))
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
            // Obtain user old Permissions before save
            $originalPermissionNames = $entity->permissions()->pluck('name')->toArray();

            if ($request->has('is_administrator')) {
                $entity->is_administrator = filter_var($request->input('is_administrator'), FILTER_VALIDATE_BOOLEAN);
                $entity->save();
            }
        } elseif ($request->input('group_id')) {
            $entity = Group::findOrFail($request->input('group_id'));
            // Obtain group old Permissions before save
            $originalPermissionNames = $entity->permissions()->pluck('name')->toArray();
        }

        // Obtain the requested permission names for that entity
        $requestPermissions = $request->input('permission_names');

        // Convert permission names into a collection of Permission models
        $permissions = Permission::whereIn('name', $requestPermissions)->get();

        // Call Event to store Permissions Changes in Log
        PermissionUpdated::dispatch(
            $requestPermissions,
            $originalPermissionNames,
            $entity->is_administrator ?: false,
            $request->input('user_id'),
            $request->input('group_id')
        );

        //Sync the entity's permissions with the database
        $entity->permissions()->sync($permissions->pluck('id')->toArray());

        // Clear user permissions cache and rebuild
        $this->clearAndRebuildCache($entity);

        return response([], 204);
    }

    private function clearAndRebuildCache($user)
    {
        // Rebuild and update the permissions cache
        $permissions = $user->permissions()->pluck('name')->toArray();
        Cache::put("user_{$user->id}_permissions", $permissions, 86400);
    }
}
