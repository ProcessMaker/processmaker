<?php

namespace ProcessMaker\Http\Controllers\Api\Administration;

use Illuminate\Support\Facades\DB;
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
        // Default order by
        $orderBy = $request->input('order_by', 'username');
        $orderDirection = $request->input('order_direction', 'asc');
 
        // Note, the current page is automatically handled by Laravel's pagination feature
        if($filter) {
            $filter = '%' . $filter . '%';
            $users = User::where('firstname', 'like', $filter)
                ->orWhere('lastname', 'like', $filter)
                ->orWhere('username', 'like', $filter)
                ->orderBy($orderBy, $orderDirection);
            if($orderBy == 'full_name') {
                // Add a calculated row of their first name
                $users = $users->select(DB::raw('users.*, CONCAT_WS(" ", firstname, lastname) as full_name'));
            } else if($orderBy == 'role') {
                // Need to join the role table and grab the code in role and order by it
                $users = $users->leftJoin('roles', 'users.role_id', 'roles.id')
                    ->select(DB::raw('users.*, roles.code as role'));
            }
            $users = $users->paginate($perPage);
        } else {
            if($orderBy == 'full_name') {
                // Add calculated full_name column
                $users = User::select(DB::raw('users.*, CONCAT_WS(" ", firstname, lastname) as full_name'))
                    ->orderBy($orderBy, $orderDirection)->paginate($perPage);
            } else if($orderBy == 'role') {
                // Join our role table if there is a role_id defined, and order by the role
                $users = User::leftJoin('roles', 'users.role_id', 'roles.id')
                    ->select(DB::raw('users.*, roles.code as role'))
                    ->orderBy($orderBy, $orderDirection)->paginate($perPage);
            } else {
                $users = User::orderBy($orderBy, $orderDirection)->paginate($perPage);
            }
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