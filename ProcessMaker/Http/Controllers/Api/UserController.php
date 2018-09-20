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
          $order_by = $request->input('order_direction');
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
     */
    public function destroy(User $user)
    {
        $user->delete();
        return response([], 204);
    }
}
