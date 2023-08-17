<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Events\UserCreated;
use ProcessMaker\Events\UserDeleted;
use ProcessMaker\Events\UserGroupMembershipUpdated;
use ProcessMaker\Events\UserRestored;
use ProcessMaker\Events\UserUpdated;
use ProcessMaker\Exception\ReferentialIntegrityException;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\Users as UserResource;
use ProcessMaker\Models\User;

class UserController extends Controller
{
    /**
     * A whitelist of attributes that should not be
     * sanitized by our SanitizeInput middleware.
     *
     * @var array
     */
    public $doNotSanitize = [
        'username', // has alpha_dash rule
        'password',
    ];

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     *
     *     @OA\Get(
     *     path="/users",
     *     summary="Returns all users",
     *     operationId="getUsers",
     *     tags={"Users"},
     *     @OA\Parameter(ref="#/components/parameters/status"),
     *     @OA\Parameter(
     *         name="filter",
     *         in="query",
     *         description="Filter results by string. Searches First Name, Last Name, Email and Username.",
     *         @OA\Schema(type="string"),
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/order_by"),
     *     @OA\Parameter(ref="#/components/parameters/order_direction"),
     *     @OA\Parameter(ref="#/components/parameters/per_page"),
     *     @OA\Parameter(ref="#/components/parameters/include"),
     *     @OA\Parameter(
     *         name="exclude_ids",
     *         in="query",
     *         description="Comma separated list of IDs to exclude from the response",
     *         @OA\Schema(type="string", default=""),
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="list of users",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/users"),
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 ref="#/components/schemas/metadata",
     *             ),
     *         ),
     *     ),
     * )
     */
    public function index(Request $request)
    {
        if (!(Auth::user()->can('view-users') ||
            Auth::user()->can('create-processes') ||
            Auth::user()->can('edit-processes'))) {
            throw new AuthorizationException(__('Not authorized to view users.'));
        }
        $query = User::nonSystem();

        $filter = $request->input('filter', '');
        if (!empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(function ($query) use ($filter) {
                $query->where('username', 'like', $filter)
                    ->orWhere('firstname', 'like', $filter)
                    ->orWhere('lastname', 'like', $filter)
                    ->orWhere('email', 'like', $filter);
            });
        }

        // Adds a parameter to exclude users by id.
        $exclude_ids = $request->input('exclude_ids', '');
        if ($exclude_ids) {
            $exclude_ids = explode(',', $exclude_ids);
            $query->whereNotIn('id', $exclude_ids);
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $order_by = 'username';
        $order_direction = 'ASC';

        if ($request->has('order_by')) {
            $order_by = $request->input('order_by');
        }

        if ($request->has('order_direction')) {
            $order_direction = $request->input('order_direction');
        }

        $response =
            $query->orderBy(
                $request->input('order_by', $order_by),
                $request->input('order_direction', $order_direction)
            )
            ->paginate($request->input('per_page', 10));

        return new ApiCollection($response);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  id  $id
     * @return \Illuminate\Http\Response
     *
     *     @OA\Post(
     *     path="/users",
     *     summary="Save a new users",
     *     operationId="createUser",
     *     tags={"Users"},
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/usersEditable")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/users")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/422"),
     * )
     */
    public function store(Request $request)
    {
        $request->validate(User::rules());

        $user = new User();
        $fields = $request->json()->all();

        if (isset($fields['password'])) {
            $fields['password'] = Hash::make($fields['password']);
        }

        $user->fill($fields);
        $user->setTimezoneAttribute($request->input('timezone', ''));
        $user->saveOrFail();
        // Register the Event
        UserCreated::dispatch($user->refresh());

        return new UserResource($user->refresh());
    }

    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *
     *     @OA\Get(
     *     path="/users/{user_id}",
     *     summary="Get single user by ID",
     *     operationId="getUserById",
     *     tags={"Users"},
     *     @OA\Parameter(
     *         description="ID of user to return",
     *         in="path",
     *         name="user_id",
     *         required=true,
     *         @OA\Schema(
     *           type="integer",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully found the process",
     *         @OA\JsonContent(ref="#/components/schemas/users")
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/404"),
     * )
     */
    public function show(User $user)
    {
        if (!Auth::user()->can('view', $user)) {
            throw new AuthorizationException(__('Not authorized to update this user.'));
        }

        return new UserResource($user);
    }

    /**
     * Return the user's pinned nodes.
     *
     * @param  User $user
     * @return \Illuminate\Http\Response
     *
     *     @OA\Get(
     *     path="/users/{user_id}/get_pinned_controls",
     *     summary="Get the pinned BPMN elements of a specific user",
     *     operationId="getPinnnedControls",
     *     tags={"Users"},
     *     @OA\Parameter(
     *         description="ID of user to return the pinned nodes of",
     *         in="path",
     *         name="user_id",
     *         required=true,
     *         @OA\Schema(
     *           type="integer",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Pinned nodes returned succesfully",
     *         @OA\JsonContent(ref="#/components/schemas/users")
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/404"),
     * )
     */
    public function getPinnnedControls(User $user)
    {
        $user = Auth::user();
        if (!$user->can('view', $user)) {
            throw new AuthorizationException(__('Not authorized to update this user.'));
        }

        $meta = $user->meta ? (array) $user->meta : [];

        return array_key_exists('pinnedControls', $meta)
                ? $meta['pinnedControls']
                : [];
    }

    /**
     * Update a user
     *
     * @param User $user
     * @param Request $request
     *
     * @return ResponseFactory|Response
     *
     *     @OA\Put(
     *     path="/users/{user_id}",
     *     summary="Update a user",
     *     operationId="updateUser",
     *     tags={"Users"},
     *     @OA\Parameter(
     *         description="ID of user to return",
     *         in="path",
     *         name="user_id",
     *         required=true,
     *         @OA\Schema(
     *           type="integer",
     *         )
     *     ),
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/usersEditable")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="success",
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/404"),
     *     @OA\Response(response=422, ref="#/components/responses/422"),
     * )
     */
    public function update(User $user, Request $request)
    {
        if (!Auth::user()->can('edit', $user)) {
            throw new AuthorizationException(__('Not authorized to update this user.'));
        }

        $request->validate(User::rules($user));
        $fields = $request->json()->all();
        if (isset($fields['password'])) {
            $fields['password'] = Hash::make($fields['password']);
        }
        $original = $user->getOriginal();
        $user->fill($fields);
        if (Auth::user()->is_administrator && $request->has('is_administrator')) {
            // user must be an admin to make another user an admin
            $user->is_administrator = $request->get('is_administrator');
        }

        $user->saveOrFail();
        $changes = $user->getChanges();

        //Call new Event to store User Changes into LOG
        UserUpdated::dispatch($user, $changes, $original);
        if ($request->has('avatar')) {
            $this->uploadAvatar($user, $request);
        }

        return response([], 204);
    }

    /**
     * Update a user's pinned BPMN elements on Modeler
     *
     * @param User $user
     * @param Request $request
     *
     * @return ResponseFactory|Response
     *
     *     @OA\Put(
     *     path="/users/{user_id}/update_pinned_controls",
     *     summary="Update a user's pinned BPMN elements on Modeler",
     *     operationId="updatePinnedControls",
     *     tags={"Users"},
     *     @OA\Parameter(
     *         description="ID of user to return",
     *         in="path",
     *         name="user_id",
     *         required=true,
     *         @OA\Schema(
     *           type="integer",
     *         )
     *     ),
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/usersEditable")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="success",
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/404"),
     *     @OA\Response(response=422, ref="#/components/responses/422"),
     * )
     */
    public function updatePinnedControls(User $user, Request $request)
    {
        $user = Auth::user();
        if (!$user->can('edit', $user)) {
            throw new AuthorizationException(__('Not authorized to update this user.'));
        }

        if ($request->has('pinnedNodes')) {
            $meta = $user->meta ? (array) $user->meta : [];
            $meta['pinnedControls'] = $request->get('pinnedNodes');
            $user->meta = $meta;
        }

        $user->saveOrFail();

        return response([], 204);
    }

    /**
     * Update a user's groups
     *
     * @param User $user
     * @param Request $request
     *
     * @return ResponseFactory|Response
     *
     *     @OA\Put(
     *     path="/users/{user_id}/groups",
     *     summary="Set the groups a users belongs to",
     *     operationId="updateUserGroups",
     *     tags={"Users"},
     *     @OA\Parameter(
     *         description="ID of user",
     *         in="path",
     *         name="user_id",
     *         required=true,
     *         @OA\Schema(
     *           type="integer",
     *         )
     *     ),
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/updateUserGroups"),
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="success",
     *     ),
     *     ),
     *
     * @OA\Schema(
     *     schema="updateUserGroups",
     *     @OA\Property(
     *         property="groups",
     *         type="array",
     *         @OA\Items(type="integer", example=1)
     *     ),
     * ),
     */
    public function updateGroups(User $user, Request $request)
    {
        $data = [];
        if ($request->has('groups')) {
            if ($request->filled('groups')) {
                $groups = $request->input('groups');
                if (!is_array($groups)) {
                    $groups = array_map('intval', explode(',', $request->groups));
                }
                $data = $user->groups()->sync($groups);
            } else {
                $user->groups()->detach();
            }
        } else {
            return response([], 400);
        }
        event(new UserGroupMembershipUpdated($data, $user));

        return response([], 204);
    }

    /**
     * Delete a user
     *
     * @param User $user
     *
     * @return ResponseFactory|Response
     *
     *     @OA\Delete(
     *     path="/users/{user_id}",
     *     summary="Delete a user",
     *     operationId="deleteUser",
     *     tags={"Users"},
     *     @OA\Parameter(
     *         description="ID of user to delete",
     *         in="path",
     *         name="user_id",
     *         required=true,
     *         @OA\Schema(
     *           type="integer",
     *         )
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="success",
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/404"),
     * )
     */
    public function destroy(User $user)
    {
        try {
            $user->delete();
            // Register the Event
            UserDeleted::dispatch($user);

            return response([], 204);
        } catch (\Exception $e) {
            abort($e->getCode(), $e->getMessage());
        } catch (ReferentialIntegrityException $e) {
            abort($e->getCode(), $e->getMessage());
        }
    }

    /**
     * Upload file avatar
     *
     * @param User $user
     * @param Request $request
     *
     * @throws \Exception
     */
    private function uploadAvatar(User $user, Request $request)
    {
        // verify data
        $data = $request->all();

        // if the avatar is an url (neither page nor file) we do not update the avatar
        if (filter_var($data['avatar'], FILTER_VALIDATE_URL)) {
            return;
        }

        if ($data['avatar'] === false) {
            $user->clearMediaCollection(User::COLLECTION_PROFILE);

            return;
        }

        if (preg_match('/^data:image\/(\w+);base64,/', $data['avatar'], $type)) {
            $data = substr($data['avatar'], strpos($data['avatar'], ',') + 1);
            $type = strtolower($type[1]); // jpg, png, gif

            if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png', 'svg'])) {
                throw new \Exception('invalid image type');
            }

            $data = base64_decode($data);

            if ($data === false) {
                throw new \Exception('base64_decode failed');
            }

            file_put_contents("/tmp/img.{$type}", $data);
            $user->addMedia("/tmp/img.{$type}")
                ->toMediaCollection(User::COLLECTION_PROFILE, User::DISK_PROFILE);
        } elseif (isset($data['avatar']) && !empty($data['avatar'])) {
            $request->validate([
                'avatar' => 'required',
            ]);

            $user->addMedia($request->avatar)
                ->toMediaCollection(User::COLLECTION_PROFILE, User::DISK_PROFILE);
        }
        $user->avatar = $user->getAvatar();
        $user->saveOrFail();
    }

    /**
     * Reverses the soft delete of a user
     *
     * @param User $user
     *
     * @OA\Put(
     *     path="/users/restore",
     *     summary="Restore a soft deleted user",
     *     operationId="restoreUser",
     *     tags={"Users"},
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/restoreUser"),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *     ),
     * )
     * @OA\Schema(
     *     schema="restoreUser",
     *     @OA\Property(
     *          property="username",
     *          type="string",
     *          description="Username to restore",
     *     ),
     * ),
     */
    public function restore(Request $request)
    {
        $user = null;

        // Look through the request data for one of these
        // keys and search trashed users with the value
        foreach (['id', 'email', 'username'] as $input) {
            // If the key isn't present,
            // skip ahead
            if (!$request->has($input)) {
                continue;
            }

            // If we already found the user to
            // restore, skip ahead
            if ($user instanceof User) {
                continue;
            }

            // Otherwise, search trashed users
            // for the user to restore
            $user = User::onlyTrashed()->where($input, $request->input($input))
                                       ->first();
        }

        if ($user instanceof User) {
            $user->restore();

            // Register the Event
            UserRestored::dispatch($user);
        }

        return response([], 200);
    }

    public function deletedUsers(Request $request)
    {
        $query = User::query()->onlyTrashed();

        $filter = $request->input('filter', '');
        if (!empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(function ($query) use ($filter) {
                $query->Where('username', 'like', $filter)
                    ->orWhere('firstname', 'like', $filter)
                    ->orWhere('lastname', 'like', $filter)
                    ->orWhere('email', 'like', $filter);
            });
        }

        $order_by = 'username';
        $order_direction = 'ASC';

        if ($request->has('order_by')) {
            $order_by = $request->input('order_by');
        }

        if ($request->has('order_direction')) {
            $order_direction = $request->input('order_direction');
        }

        $response =
            $query->orderBy(
                $request->input('order_by', $order_by),
                $request->input('order_direction', $order_direction)
            )
            ->paginate($request->input('per_page', 10));

        // return $deletedUsers;
        return new ApiCollection($response);
    }
}
