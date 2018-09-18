<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Models\Group;
use ProcessMaker\Http\Resources\Groups as GroupResource;

class GroupController extends Controller
{
  /**
   * Display a listing of the resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function index(Request $request)
  {
      $query = Group::query();

      $filter = $request->input('filter', '');
      if (!empty($filter)) {
          $filter = '%' . $filter . '%';
          $query->where(function ($query) use ($filter) {
              $query->Where('name', 'like', $filter);
          });
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
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\Response
   */
  public function store(Request $request)
  {
      $request->validate(Group::rules());
      $group = new Group();
      $group->fill($request->input());
      $group->saveOrFail();
      return new GroupResource($group);
  }

  /**
   * Display the specified resource.
   *
   * @param  uuid  $id
   * @return \Illuminate\Http\Response
   */
  public function show(Group $group)
  {
      return new GroupResource($group);
  }

  /**
   * Show the form for editing the specified resource.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function edit($id)
  {
      //
  }

  /**
   * Update a user
   *
   * @param Group $user
   * @param Request $request
   *
   * @return ResponseFactory|Response
   */
  public function update(Group $group, Request $request)
  {
      $request->validate(Group::rules());

      $group->fill($request->input());
      $group->saveOrFail();

      return response([], 204);
  }

  /**
   * Delete a user
   *
   * @param Group $user
   *
   * @return ResponseFactory|Response
   */
  public function destroy(Group $group)
  {
      $group->delete();
      return response([], 204);
  }
}
