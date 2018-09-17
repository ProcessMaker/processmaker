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

        $response =
            $query->orderBy(
                $request->input('order_by', 'username'),
                $request->input('order_direction', 'ASC')
            )
            ->paginate($request->input('per_page', 10));

        return new ApiCollection($response);
    }

    // /**
    //  * Create a new user
    //  *
    //  * @param Request $request
    //  *
    //  * @return ResponseFactory|Response
    //  */
    // public function store(Request $request)
    // {
    //     $request->validate(User::rules());
    //
    //     $data = [
    //       'username' => $request->input('username', ''),
    //       'email' => $request->input('email', ''),
    //       'password' => $request->input('password', ''),
    //       'firstname' => $request->input('firstname', ''),
    //       'lastname' => $request->input('lastname', ''),
    //       'status' => $request->input('status', ''),
    //       'address' => $request->input('address', ''),
    //       'city' => $request->input('city', ''),
    //       'state' => $request->input('state', ''),
    //       'postal' => $request->input('postal', ''),
    //       'country' => $request->input('country', ''),
    //       'phone' => $request->input('phone', ''),
    //       'fax' => $request->input('fax', ''),
    //       'cell' => $request->input('cell', ''),
    //       'title' => $request->input('title', ''),
    //       'birthdate' => $request->input('birthdate', ''),
    //       'timezone' => $request->input('timezone', ''),
    //       'language' => $request->input('language', ''),
    //       'expires_at' => $request->input('expires_at', ''),
    //     ];
    //
    //     $user = User::save($data);
    //     return new UserResource($user);
    // }

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
