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
     */
    public function index(Request $request)
    {
        $query = GroupMember::query();

        $filter = $request->input('filter', '');
        if (!empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(function ($query) use ($filter) {
                $query->Where('member_uuid', '=', $filter);
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
     */
    public function store(Request $request)
    {
        $request->validate(GroupMember::rules());

        // $group = Group::withUuid($request->input('group_uuid'))->first();
        $group = Group::find($request->input('group_uuid'));
        $member = $request->input('member_type')::withUuid(
            $request->input('member_uuid')
        )->first();
        $group_member = new GroupMember();
        $group_member->group()->associate($group);
        $group_member->member()->associate($member);
        try {
            $group_member->saveOrFail();
        } catch (Illuminate\Database\QueryException $e) {
            die("HEREEEEEEEEEEEEEEEEe");
        }

        return response(new GroupMemberResource($group_member), 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  uuid  $id
     * @return \Illuminate\Http\Response
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
     */
    public function destroy(GroupMember $group_member)
    {
        $group_member->delete();
        return response([], 204);
    }
}
