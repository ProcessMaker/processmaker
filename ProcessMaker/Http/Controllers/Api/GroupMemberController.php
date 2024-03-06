<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Events\GroupUsersUpdated;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\GroupMembers as GroupMemberResource;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\User;

class GroupMemberController extends Controller
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
     *     path="/group_members",
     *     summary="Returns all groups for a given member",
     *     operationId="getGroupMembers",
     *     tags={"Group Members"},
     *     @OA\Parameter(ref="#/components/parameters/member_id"),
     *     @OA\Parameter(ref="#/components/parameters/order_by"),
     *     @OA\Parameter(ref="#/components/parameters/order_direction"),
     *     @OA\Parameter(ref="#/components/parameters/per_page"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="list of group_members",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/groupMembers"),
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
        if (!(Auth::user()->can('view-groups') || Auth::user()->can('view-users'))) {
            throw new AuthorizationException(__('Not authorized to view groups.'));
        }

        $query = GroupMember::query()
            ->join('groups', 'groups.id', '=', 'group_members.group_id')
            ->select('group_members.*', 'groups.name', 'groups.description');

        if (\Auth::user()->is_administrator) {
            $member_id = $request->input('member_id', null);
            if ($member_id) {
                $query->where('member_id', $member_id);
            }
        } else {
            $query->where('member_id', Auth::user()->id);
        }

        $response =
            $query->orderBy(
                $request->input('order_by', 'created_at'),
                $request->input('order_direction', 'ASC')
            )->paginate($request->input('per_page', 10));

        return new ApiCollection($response);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws \Throwable
     *
     * @OA\Post(
     *     path="/group_members",
     *     summary="Save a new group member",
     *     operationId="createGroupMember",
     *     tags={"Group Members"},
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/groupMembersEditable")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/createGroupMembers")
     *     ),
     * )
     */
    public function store(Request $request)
    {
        $isMemberAssociated = GroupMember::where('group_id', $request->input('group_id'))
            ->where('member_type', $request->input('member_type'))
            ->where('member_id', $request->input('member_id'))
            ->count();

        if ($isMemberAssociated) {
            return response([], 201);
        }

        $request->validate(GroupMember::rules());

        $group = Group::findOrFail($request->input('group_id'));

        $member = $request->input('member_type')::where('id', $request->input('member_id'))->firstOrFail();

        $group_member = new GroupMember();
        $group_member->group()->associate($group);
        $group_member->member()->associate($member);
        $group_member->saveOrFail();

        event(new GroupUsersUpdated($group->id, $request->input('member_id'), GroupUsersUpdated::ADDED, $request->input('member_type')));

        return response(new GroupMemberResource($group_member), 201);
    }

    /**
     * Display the specified resource.
     *
     * @param GroupMember $group_member
     *
     * @return GroupMemberResource
     *
     * @OA\Get(
     *     path="/group_members/{group_member_id}",
     *     summary="Get single group member by ID",
     *     operationId="getGroupMemberById",
     *     tags={"Group Members"},
     *     @OA\Parameter(
     *         description="ID of group members to return",
     *         in="path",
     *         name="group_member_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully found the group members",
     *         @OA\JsonContent(ref="#/components/schemas/getGroupMembersById")
     *     ),
     * )
     */
    public function show(GroupMember $group_member)
    {
        return new GroupMemberResource($group_member);
    }

    /**
     * Delete a group membership
     *
     * @param GroupMember $group_member
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     *
     * @throws \Exception
     *
     * @OA\Delete(
     *     path="/group_members/{group_member_id}",
     *     summary="Delete a group member",
     *     operationId="deleteGroupMember",
     *     tags={"Group Members"},
     *     @OA\Parameter(
     *         description="ID of group_members to return",
     *         in="path",
     *         name="group_member_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="success",
     *     ),
     * )
     */
    public function destroy(GroupMember $group_member)
    {
        $group_member->delete();

        event(new GroupUsersUpdated($group_member->group_id, $group_member->member_id, GroupUsersUpdated::DELETED, $group_member->member_type));

        return response([], 204);
    }

    /**
     * Display a listing of groups available
     *
     * @param  \Illuminate\Http\Request $request
     * @return ApiCollection
     *
     * @OA\Get(
     *     path="/group_members_available",
     *     summary="Returns all groups available for a given member",
     *     operationId="getGroupMembersAvailable",
     *     tags={"Group Members"},
     *     @OA\Parameter(
     *         description="ID of group member to return",
     *         in="path",
     *         name="member_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Parameter(
     *         description="type of group member to return",
     *         in="path",
     *         name="member_type",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/filter"),
     *     @OA\Parameter(ref="#/components/parameters/order_by"),
     *     @OA\Parameter(ref="#/components/parameters/order_direction"),
     *     @OA\Parameter(ref="#/components/parameters/per_page"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="list of groups available to be assigned as member",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/availableGroupMembers"),
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
    public function groupsAvailable(Request $request)
    {
        $group_id = $request->input('group_id', null);
        $member_id = $request->input('member_id', null);
        $member_type = $request->input('member_type', null);
        $assignedResult = collect([]);

        if ($request->input('order_by') == 'assigned' && $member_id && $member_type) {
            $orderByAssigned = true;
        } else {
            $orderByAssigned = false;
        }

        if ($orderByAssigned) {
            $orderBy = 'name';
        } else {
            $orderBy = $request->input('order_by', 'name');
        }

        $members = [];
        if ($group_id) {
            $members = GroupMember::where('member_type', Group::class)
                ->where('group_id', $group_id)
                ->get()->pluck('member_id');
            $members->push($group_id);
        } elseif ($member_id && $member_type) {
            //Load groups already assigned.
            $members = GroupMember::where('member_type', $member_type)
                ->where('member_id', $member_id)
                ->get()->pluck('group_id');
        }

        $query = Group::where('status', 'ACTIVE');

        $filter = $request->input('filter', '');
        if (!empty($filter)) {
            //filter by name group
            $filter = '%' . $filter . '%';
            $query->where(function ($query) use ($filter) {
                $query->Where('name', 'like', $filter);
            });
        }

        $query->orderBy(
            $orderBy,
            $request->input('order_direction', 'ASC')
        );

        if ($orderByAssigned) {
            $assignedQuery = clone $query;
            $assignedQuery->whereIn('id', $members);
        }

        $query->whereNotIn('id', $members);

        $response = $query->get();

        if ($orderByAssigned) {
            $assignedResponse = $assignedQuery->get();
            $response = $assignedResponse->merge($response);

            $response = $response->map(function ($group) use ($members) {
                $group->assigned = $members->contains($group->id);

                return $group;
            })->values();
        }

        return new ApiCollection($response);
    }

    /**
     * Display a listing of users available
     *
     * @param  \Illuminate\Http\Request $request
     * @return ApiCollection
     *
     * @OA\Get(
     *     path="/user_members_available",
     *     summary="Returns all users available for a given group",
     *     operationId="getUserMembersAvailable",
     *     tags={"Group Members"},
     *     @OA\Parameter(
     *         description="ID of group to return",
     *         in="path",
     *         name="group_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="filter",
     *         in="query",
     *         description="Filter results by string. Searches Name. Can be a substring.",
     *         @OA\Schema(type="string"),
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/order_by"),
     *     @OA\Parameter(ref="#/components/parameters/order_direction"),
     *     @OA\Parameter(ref="#/components/parameters/per_page"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="list of users available to be assigned as member",
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
    public function usersAvailable(Request $request)
    {
        $groupId = $request->input('group_id', null);

        $members = [];
        if ($groupId) {
            //Load user members already assigned.
            $data = GroupMember::where('member_type', User::class)
                ->where('group_id', $groupId)
                ->get();
            foreach ($data as $item) {
                array_push($members, $item->member_id);
            }
        }

        $query = User::nonSystem()
            ->where('status', '!=', 'INACTIVE')
            ->whereNotIn('id', $members);

        $filter = $request->input('filter', '');
        if (!empty($filter)) {
            //filter by name group
            $filter = '%' . $filter . '%';
            $query->where(function ($query) use ($filter) {
                $query->where('firstname', 'like', $filter)
                      ->orWhere('lastname', 'like', $filter)
                      ->orWhere('username', 'like', $filter);
            });
        }
        $response =
            $query->orderBy(
                $request->input('order_by', 'firstname'),
                $request->input('order_direction', 'ASC')
            )->paginate($request->input('per_page', 10));

        return new ApiCollection($response);
    }
}
