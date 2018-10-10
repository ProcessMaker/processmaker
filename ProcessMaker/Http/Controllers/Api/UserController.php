<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Models\User;
use ProcessMaker\Http\Resources\Users as UserResource;

class UserController extends Controller
{
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
     *     @OA\Parameter(ref="#/components/parameters/filter"),
     *     @OA\Parameter(ref="#/components/parameters/order_by"),
     *     @OA\Parameter(ref="#/components/parameters/order_direction"),
     *     @OA\Parameter(ref="#/components/parameters/per_page"),
     *     @OA\Parameter(ref="#/components/parameters/include"),
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
     *                 allOf={@OA\Schema(ref="#/components/schemas/metadata")},
     *             ),
     *         ),
     *     ),
     * )
     */
    public function index(Request $request)
    {
        $query = User::query();

        $filter = $request->input('filter', '');
        if (!empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(function ($query) use ($filter) {
                $query->Where('username', 'like', $filter)
                    ->orWhere('firstname', 'like', $filter)
                    ->orWhere('lastname', 'like', $filter);
            });
        }

        $order_by = 'username';
        $order_direction = 'ASC';

        if($request->has('order_by')){
          $order_by = $request->input('order_by');
        }

        if($request->has('order_direction')){
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * 
     *     @OA\Get(
     *     path="/users/{userUuid}",
     *     summary="Get single user by ID",
     *     operationId="getUsersByUuid",
     *     tags={"Users"},
     *     @OA\Parameter(
     *         description="ID of user to return",
     *         in="path",
     *         name="userUuid",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully found the process",
     *         @OA\JsonContent(ref="#/components/schemas/users")
     *     ),
     * )
     */
    public function store(Request $request)
    {
        $request->validate(User::rules());
        $user = new User();
        $user->fill($request->input());
        $user->saveOrFail();
        return new UserResource($user);
    }

    /**
     * Display the specified resource.
     *
     * @param  uuid  $id
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
     * )
     */
    public function show(User $user)
    {
        return new UserResource($user);
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
     *     path="/users/{userUuid}",
     *     summary="Update a user",
     *     operationId="updateUsers",
     *     tags={"Users"},
     *     @OA\Parameter(
     *         description="ID of user to return",
     *         in="path",
     *         name="userUuid",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/usersEditable")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/users")
     *     ),
     * )
     */
    public function update(User $user, Request $request)
    {
        $request->validate(User::rules($user));
        $user->fill($request->input());
        $user->saveOrFail();
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
     *     path="/users/{userUuid}",
     *     summary="Delete a user",
     *     operationId="deleteUser",
     *     tags={"Users"},
     *     @OA\Parameter(
     *         description="ID of user to return",
     *         in="path",
     *         name="userUuid",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/users")
     *     ),
     * )
     */
    public function destroy(User $user)
    {
        $user->delete();
        return response([], 204);
    }
}
