<?php

namespace ProcessMaker\Http\Controllers\Api\Administration;

use Illuminate\Http\Request;
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
     * @return JsonResponse A list of matched users and paging data
     */
    public function index(Request $request)
    {
        // Grab pagination data
        $perPage = $request->input('per_page', 10);
        // Filter
        $filter = $request->input('filter', null);
        // Note, the current page is automatically handled by Laravel's pagination feature
        if($filter) {
            $filter = '%' . $filter . '%';
            $users = User::where('firstname', 'like', $filter)
                ->orWhere('lastname', 'like', $filter)
                ->orWhere('username', 'like', $filter)
                ->paginate($perPage);
        } else {
            $users = User::paginate($perPage);
        }
        // Return fractal representation of paged data
        return fractal($users, new UserTransformer())->respond();
    }

    /**
     * Fetch a single user from the system and return
     */
    public function get(Request $request, User $user)
    {
        return fractal($user, new UserTransformer())->respond();

    }

}