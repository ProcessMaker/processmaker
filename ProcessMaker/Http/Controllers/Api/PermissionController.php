<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use ProcessMaker\Events\PermissionUpdated;
use ProcessMaker\Http\Controllers\Controller;
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
     * @return \Illuminate\Support\Collection
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
     * @return \Illuminate\Http\Response
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
        $request->validate([
            'user_id' => 'required_without:group_id|integer',
            'group_id' => 'required_without:user_id|integer',
            'permission_names' => 'nullable|array',
        ]);

        if ($request->filled('user_id') && $request->filled('group_id')) {
            throw ValidationException::withMessages([
                'user_id' => [__('The user_id field cannot be present when group_id is present.')],
                'group_id' => [__('The group_id field cannot be present when user_id is present.')],
            ]);
        }

        //Obtain the requested user or group
        if ($request->filled('user_id')) {
            $this->authorize('edit-users');
            $entity = User::findOrFail($request->input('user_id'));
            // Obtain user old Permissions before save
            $originalPermissionNames = $entity->permissions()->pluck('name')->toArray();
            if ($request->has('is_administrator')) {
                $isSettingToAdmin = $request->boolean('is_administrator');

                if ((!Auth::user()->is_administrator && $entity->is_administrator) ||
                    ($isSettingToAdmin && !Auth::user()->is_administrator)) {
                    return response()->json(['message' => 'You are not authorized to modify administrator privileges'], 403);
                }

                $entity->is_administrator = $isSettingToAdmin;
                $entity->save();
            }
        } elseif ($request->filled('group_id')) {
            $this->authorize('edit-groups');
            $entity = Group::findOrFail($request->input('group_id'));
            // Obtain group old Permissions before save
            $originalPermissionNames = $entity->permissions()->pluck('name')->toArray();
        }

        // Obtain the requested permission names for that entity
        $requestPermissions = $request->input('permission_names') ?? [];

        // Convert permission names into a collection of Permission models
        $permissions = Permission::whereIn('name', $requestPermissions)->get();

        // Call Event to store Permissions Changes in Log
        PermissionUpdated::dispatch(
            $requestPermissions,
            $originalPermissionNames,
            $entity instanceof User ? $entity->is_administrator : false,
            $request->input('user_id'),
            $request->input('group_id')
        );

        //Sync the entity's permissions with the database
        $entity->permissions()->sync($permissions->pluck('id')->toArray());

        // The PermissionUpdated event will automatically trigger cache invalidation
        // via the InvalidatePermissionCacheOnUpdate listener

        return response([], 204);
    }
}
