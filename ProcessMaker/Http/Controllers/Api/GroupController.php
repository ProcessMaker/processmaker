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
   * 
   *       @OA\Get(
    *     path="/groups",
    *     summary="Returns all groups that the user has access to",
    *     operationId="getGroups",
    *     tags={"Groups"},
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
    *                 allOf={@OA\Schema(ref="#/components/schemas/metadata")},
    *             ),
    *         ),
    *     ),
    * )
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
   * 
    *     @OA\Post(
    *     path="/groups",
    *     summary="Save a new groups",
    *     operationId="createGroup",
    *     tags={"Groups"},
    *     @OA\RequestBody(
    *       required=true,
    *       @OA\JsonContent(ref="#/components/schemas/groupsEditable")
    *     ),
    *     @OA\Response(
    *         response=201,
    *         description="success",
    *         @OA\JsonContent(ref="#/components/schemas/groups")
    *     ),
    * )
   * 
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
   * 
   *       @OA\Get(
     *     path="/groups/{groupUuid}",
     *     summary="Get single group by ID",
     *     operationId="getGroupByUuid",
     *     tags={"Groups"},
     *     @OA\Parameter(
     *         description="ID of group to return",
     *         in="path",
     *         name="groupUuid",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully found the group",
     *         @OA\JsonContent(ref="#/components/schemas/groups")
     *     ),
     * )
   */
  public function show(Group $group)
  {
      return new GroupResource($group);
  }

  /**
   * Update a user
   *
   * @param Group $user
   * @param Request $request
   *
   * @return ResponseFactory|Response
   * 
    *     @OA\Put(
    *     path="/groups/{groupUuid}",
    *     summary="Update a group",
    *     operationId="updateGroup",
    *     tags={"Groups"},
    *     @OA\Parameter(
    *         description="ID of group to return",
    *         in="path",
    *         name="groupUuid",
    *         required=true,
    *         @OA\Schema(
    *           type="string",
    *         )
    *     ),
    *     @OA\RequestBody(
    *       required=true,
    *       @OA\JsonContent(ref="#/components/schemas/groupsEditable")
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="success",
    *         @OA\JsonContent(ref="#/components/schemas/groups")
    *     ),
    * )
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
   * 
    *     @OA\Delete(
    *     path="/groups/{groupUuid}",
    *     summary="Delete a group",
    *     operationId="deleteGroup",
    *     tags={"Groups"},
    *     @OA\Parameter(
    *         description="ID of group to return",
    *         in="path",
    *         name="groupUuid",
    *         required=true,
    *         @OA\Schema(
    *           type="string",
    *         )
    *     ),
    *     @OA\Response(
    *         response=204,
    *         description="success",
    *         @OA\JsonContent(ref="#/components/schemas/groups")
    *     ),
    * )
   */
  public function destroy(Group $group)
  {
      $group->delete();
      return response([], 204);
  }
}
