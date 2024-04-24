<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Events\GroupCreated;
use ProcessMaker\Events\GroupDeleted;
use ProcessMaker\Events\GroupUpdated;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\Groups as GroupResource;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\User;

class GroupController extends Controller
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
     * Display a listing of the resource.
     *
     * @param Request $request
     *
     * @return ApiCollection
     *
     * @OA\Get(
     *     path="/groups",
     *     summary="Returns all groups that the user has access to",
     *     operationId="getGroups",
     *     tags={"Groups"},
     *     @OA\Parameter(ref="#/components/parameters/status"),
     *     @OA\Parameter(ref="#/components/parameters/filter"),
     *     @OA\Parameter(ref="#/components/parameters/order_by"),
     *     @OA\Parameter(ref="#/components/parameters/order_direction"),
     *     @OA\Parameter(ref="#/components/parameters/per_page"),
     *     @OA\Parameter(ref="#/components/parameters/include"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="list of groups",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/groups"),
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
        if (!(Auth::user()->can('view-groups') ||
            Auth::user()->can('create-processes') ||
            Auth::user()->can('edit-processes') ||
            Auth::user()->can('create-projects') ||
            Auth::user()->can('view-projects'))) {
            throw new AuthorizationException(__('Not authorized to view groups.'));
        }
        $include = $request->input('include', '');
        $query = Group::query();
        if ($include) {
            $include = explode(',', $include);
            $count = array_search('membersCount', $include);
            if ($count !== false) {
                unset($include[$count]);
                $query->withCount('groupMembers');
            }
            if ($include) {
                $query->with($include);
            }
        }
        $filter = $request->input('filter', '');
        if (!empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(function ($query) use ($filter) {
                $query->Where('name', 'like', $filter)
                    ->orWhere('description', 'like', $filter);
            });
        }
        $status = $request->input('status', null);
        if ($status) {
            $query->where('status', $status);
        }

        $response =
            $query->orderBy(
                $request->input('order_by', 'name'),
                $request->input('order_direction', 'ASC')
            )
                ->paginate($request->input('per_page', 10));

        return new ApiCollection($response);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     *
     * @return GroupResource
     * @throws \Throwable
     *
     * @OA\Post(
     *     path="/groups",
     *     summary="Save a new group",
     *     operationId="createGroup",
     *     tags={"Groups"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/groupsEditable")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/groups")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/422"),
     * )
     */
    public function store(Request $request)
    {
        $request->validate(Group::rules());

        // Check if 2FA is enabled and set the enabled_2fa flag
        if (config('password-policies.2fa_enabled', false)) {
            $request->merge(['enabled_2fa' => true]);
        }

        $group = new Group();
        $group->fill($request->input());
        $group->saveOrFail();
        // Register the Event
        GroupCreated::dispatch($group);

        return new GroupResource($group);
    }

    /**
     * Display the specified resource.
     *
     * @param Group $group
     * @return GroupResource
     *
     * @OA\Get(
     *     path="/groups/{group_id}",
     *     summary="Get single group by ID",
     *     operationId="getGroupById",
     *     tags={"Groups"},
     *     @OA\Parameter(
     *         description="ID of group to return",
     *         in="path",
     *         name="group_id",
     *         required=true,
     *         @OA\Schema(
     *           type="integer",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully found the group",
     *         @OA\JsonContent(ref="#/components/schemas/groups")
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/404"),
     * )
     */
    public function show(Group $group)
    {
        if (!(Auth::user()->can('view-groups') ||
            Auth::user()->can('create-processes') ||
            Auth::user()->can('edit-processes'))) {
            throw new AuthorizationException(__('Not authorized to view groups.'));
        }

        return new GroupResource($group);
    }

    /**
     * Update a user
     *
     * @param Group $group
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws \Throwable
     *
     * @OA\Put(
     *     path="/groups/{group_id}",
     *     summary="Update a group",
     *     operationId="updateGroup",
     *     tags={"Groups"},
     *     @OA\Parameter(
     *         description="ID of group to return",
     *         in="path",
     *         name="group_id",
     *         required=true,
     *         @OA\Schema(
     *           type="integer",
     *         )
     *     ),
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/groupsEditable")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="success",
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/404"),
     * )
     */
    public function update(Group $group, Request $request)
    {
        $request->validate(Group::rules($group));
        $original = $group->getOriginal();
        $group->fill($request->input());
        $group->saveOrFail();
        $changes = $group->getChanges();
        // Register the Event
        GroupUpdated::dispatch($group, $changes, $original);

        return response([], 204);
    }

    /**
     * Delete a user
     *
     * @param Group $group
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws \Exception
     *
     * @OA\Delete(
     *     path="/groups/{group_id}",
     *     summary="Delete a group",
     *     operationId="deleteGroup",
     *     tags={"Groups"},
     *     @OA\Parameter(
     *         description="ID of group to return",
     *         in="path",
     *         name="group_id",
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
    public function destroy(Group $group)
    {
        // Get the current user members
        $usersMembers = GroupMember::where([
            ['group_id', '=', $group->id],
            ['member_type', '=', User::class],
        ])->get()->toArray();
        // Get the current group members
        $groupMembers = GroupMember::where([
            ['group_id', '=', $group->id],
            ['member_type', '=', Group::class],
        ])->get()->toArray();

        $group->delete();

        // Register the Event
        GroupDeleted::dispatch($group, $usersMembers, $groupMembers);

        return response([], 204);
    }

    /**
     * Display the list of users in a group
     *
     * @param Request $request
     *
     * @return ApiCollection
     *
     * @OA\Get(
     *     path="/groups/{group_id}/users",
     *     summary="Returns all users of a group",
     *     operationId="getGroupUsers",
     *     tags={"Groups"},
     *     @OA\Parameter(
     *         description="ID of group",
     *         in="path",
     *         name="group_id",
     *         required=true,
     *         @OA\Schema(
     *           type="integer",
     *         )
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/filter"),
     *     @OA\Parameter(ref="#/components/parameters/order_direction"),
     *     @OA\Parameter(ref="#/components/parameters/per_page"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="list of members of a group",
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
    public function users(Group $group, Request $request)
    {
        $query = User::query()
            ->leftJoin('group_members', 'users.id', '=', 'group_members.member_id');

        $query->where('group_members.group_id', $group->id);

        $query->where('group_members.member_type', User::class);

        $filter = $request->input('filter', '');
        if (!empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(function ($query) use ($filter) {
                $query->Where('username', 'like', $filter)
                    ->orWhere('firstname', 'like', $filter)
                    ->orWhere('lastname', 'like', $filter)
                    ->orWhereRaw("trim(concat(firstname, ' ', lastname)) like '{$filter}'");
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

        return new ApiCollection($response);
    }

    /**
     * Display the list of groups in a group
     *
     * @param Request $request
     *
     * @return ApiCollection
     *
     * @OA\Get(
     *     path="/groups/{group_id}/groups",
     *     summary="Returns all users of a group",
     *     operationId="getGroupGroupss",
     *     tags={"Groups"},
     *     @OA\Parameter(
     *         description="ID of group",
     *         in="path",
     *         name="group_id",
     *         required=true,
     *         @OA\Schema(
     *           type="integer",
     *         )
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/filter"),
     *     @OA\Parameter(ref="#/components/parameters/order_direction"),
     *     @OA\Parameter(ref="#/components/parameters/per_page"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="list of members of a group",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/groups"),
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
    public function groups(Group $group, Request $request)
    {
        $query = Group::query()
            ->leftJoin('group_members', 'groups.id', '=', 'group_members.member_id');

        $query->where('group_members.group_id', $group->id);

        $query->where('group_members.member_type', Group::class);

        $filter = $request->input('filter', '');
        if (!empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(function ($query) use ($filter) {
                $query->Where('name', 'like', $filter)
                    ->orWhere('description', 'like', $filter);
            });
        }

        $order_by = 'name';
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
}
