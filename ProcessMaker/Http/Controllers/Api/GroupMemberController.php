<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
              $query->Where('name', 'like', $filter);
          });
      }

      $order_by = 'created_at';
      $order_direction = 'ASC';

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

      $uuid = Str::uuid();

      \DB::insert('insert into group_members (uuid, member_uuid, group_uuid, member_type, created_at, updated_at) values (?, ?, ?, ?, NOW(), NOW())', [
        GroupMember::encodeUuid($uuid),
        GroupMember::encodeUuid($request->input('member_uuid')),
        GroupMember::encodeUuid($request->input('group_uuid')),
        $request->input('member_type')
      ]);

      $group_member = GroupMember::withUuid($uuid)->first();

      $response = new GroupMemberResource($group_member);

      return response($response, 201);
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
