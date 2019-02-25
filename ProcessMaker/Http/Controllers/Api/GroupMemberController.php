<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;

use ProcessMaker\Http\Controllers\Controller;

use ProcessMaker\Models\User;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\GroupMembers as GroupMemberResource;

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
     * @return \Illuminate\Http\Response
     *
     *     @OA\Get(
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
     *                 @OA\Items(ref="#/components/schemas/group_members"),
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 allOf={@OA\Schema(ref="#/components/schemas/metadata")},
     *             ),
     *         ),
     *     ),
     * )
     */
    public function index(Request $request)
    {
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *
     *     @OA\Post(
     *     path="/group_members",
     *     summary="Save a new group_members",
     *     operationId="createGroupMembers",
     *     tags={"Group Members"},
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/group_membersEditable")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/group_members")
     *     ),
     * )
     */
    public function store(Request $request)
    {
        $isMemberAssociated = GroupMember::where('group_id', $request->input('group_id'))
            ->where ('member_type', $request->input('member_type'))
            ->where ('member_id', $request->input('member_id'))
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

        return response(new GroupMemberResource($group_member), 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  id  $id
     * @return \Illuminate\Http\Response
     *
     *     @OA\Get(
     *     path="/group_members/group_memberId",
     *     summary="Get single group_member by ID",
     *     operationId="getGroupMemberById",
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
     *         response=200,
     *         description="Successfully found the group_members",
     *         @OA\JsonContent(ref="#/components/schemas/group_members")
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
     * @param GroupMember $user
     *
     * @return ResponseFactory|Response
     *
     *     @OA\Delete(
     *     path="/group_members/group_memberId",
     *     summary="Delete a group_members",
     *     operationId="deleteGroupMembers",
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
     *         @OA\JsonContent(ref="#/components/schemas/group_members")
     *     ),
     * )
     */
    public function destroy(GroupMember $group_member)
    {
        $group_member->delete();
        return response([], 204);
    }

    /**
     * Display a listing of groups available
     *
     * @param  \Illuminate\Http\Request  $request
     * @return ApiCollection
     *
     * @OA\Get(
     *     path="/group_members_available",
     *     summary="Returns all groups available for a given member",
     *     operationId="getGroupMembersAvailable",
     *     tags={"Group Members"},
     *     @OA\Parameter(
     *         description="ID of group_members to return",
     *         in="path",
     *         name="member_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Parameter(
     *         description="type of group_members to return",
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
     *                 @OA\Items(ref="#/components/schemas/group_members"),
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 allOf={@OA\Schema(ref="#/components/schemas/metadata")},
     *             ),
     *         ),
     *     ),
     * )
     */
    public function groupsAvailable(Request $request)
    {
        $member_id = $request->input('member_id', null);
        $member_type = $request->input('member_type', null);

        $members = [];
        if ($member_id && $member_type) {
            //Load groups already assigned.
            $data = GroupMember::where('member_type', $member_type)
                ->where('member_id', $member_id)
                ->get();
            foreach ($data as $item) {
                array_push($members, $item->group_id);
            }
        }

        $query = Group::where('status', 'ACTIVE')
            ->whereNotIn('id', $members);

        $filter = $request->input('filter', '');
        if (!empty($filter)) {
            //filter by name group
            $filter = '%' . $filter . '%';
            $query->where(function ($query) use ($filter) {
                $query->Where('name', 'like', $filter);
            });
        }
        $response =
            $query->orderBy(
                $request->input('order_by', 'name'),
                $request->input('order_direction', 'ASC')
            )->paginate($request->input('per_page', 10));

        return new ApiCollection($response);
    }

    /**
     * Display a listing of users available
     *
     * @param  \Illuminate\Http\Request  $request
     * @return ApiCollection
     *
     * @OA\Get(
     *     path="/user_members_available",
     *     summary="Returns all users available for a given member",
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
     *     @OA\Parameter(ref="#/components/parameters/filter"),
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
     *                 @OA\Items(ref="#/components/schemas/group_members"),
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 allOf={@OA\Schema(ref="#/components/schemas/metadata")},
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

        $query = User::where('status', 'ACTIVE')
            ->whereNotIn('id', $members);

        $filter = $request->input('filter', '');
        if (!empty($filter)) {
            //filter by name group
            $filter = '%' . $filter . '%';
            $query->where(function ($query) use ($filter) {
                $query->Where('firstname', 'like', $filter)
                    ->orWhere('lastname', 'like', $filter);
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
