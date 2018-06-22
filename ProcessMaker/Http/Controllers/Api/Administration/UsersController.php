<?php

namespace ProcessMaker\Http\Controllers\Api\Administration;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ProcessMaker\Facades\UserManager;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\User;
use ProcessMaker\Transformers\UserTransformer;


/**
 * Controller that handles all Users API endpoints
 *
 */
class UsersController extends Controller
{

    /**
     * Fetch a collection of users based on paged request and filter if provided
     *
     * @param Request $request
     *
     * @return ResponseFactory|Response A list of matched users and paging data
     */
    public function index(Request $request)
    {
        $options = [
            'filter' => $request->input('filter', ''),
            'current_page' => $request->input('current_page', 1),
            'per_page' => $request->input('per_page', 10),
            'sort_by' => $request->input('sort_by', 'username'),
            'order_direction' => $request->input('order_direction', 'ASC'),
        ];
        $response = UserManager::index($options);
        // Return fractal representation of paged data
        return fractal($response, new UserTransformer())->respond();
    }

    /**
     * Fetch a single user from the system and return
     *
     ** @param User $user
     *
     * @return ResponseFactory|Response Information user
     */
    public function get(User $user)
    {
        return fractal($user, new UserTransformer())->respond();
    }

    /**
     * Update information user
     *
     * @param User $user
     * @param Request $request
     *
     * @return ResponseFactory|Response
     * @throws \Throwable
     */
    public function update(User $user, Request $request)
    {
        UserManager::update($user, $request);
        return response([], 200);
    }

    /**
     * Fetch an avatar for a user
     * If the avatar is not uploaded, return a JSON error response, with the user provided
     */
    public function avatar(Request $request, User $user)
    {
        // Testing, just return an error
        return response([
            'message' => 'No avatar was uploaded for the requested user',
            'user' => fractal($user, new UserTransformer())->toArray()
        ], 404);
    }

}
