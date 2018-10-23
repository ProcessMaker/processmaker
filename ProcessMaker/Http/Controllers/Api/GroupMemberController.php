<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;

use ProcessMaker\Http\Controllers\Controller;

use ProcessMaker\Models\User;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;

use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\GroupMembers as GroupMemberResource;

class GroupMemberController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     *
     *     @OA\Get(
     *     path="/group_members",
     *     summary="Returns all group_members that the user has access to",
     *     operationId="getGroupMembers",
     *     tags={"Group members"},
     *     @OA\Parameter(ref="#/components/parameters/filter"),
     *     @OA\Parameter(ref="#/components/parameters/order_by"),
     *     @OA\Parameter(ref="#/components/parameters/order_direction"),
     *     @OA\Parameter(ref="#/components/parameters/per_page"),
     *     @OA\Parameter(ref="#/components/parameters/include"),
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
        $query = GroupMember::query();

        $filter = $request->input('filter', '');
        if (!empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(function ($query) use ($filter) {
                $query->Where('member_id', '=', $filter);
            });
        }

        $response =
            $query->orderBy(
            $request->input('order_by', 'created_at'),
            $request->input('order_direction', 'ASC')
        )
            ->paginate($request->input('per_page', 10));

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
     *     tags={"Group members"},
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
     *     tags={"Group members"},
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
     * Delete a user
     *
     * @param GroupMember $user
     *
     * @return ResponseFactory|Response
     *
     *     @OA\Delete(
     *     path="/group_members/group_memberId",
     *     summary="Delete a group_members",
     *     operationId="deleteGroupMembers",
     *     tags={"Group members"},
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
}
