<?php

namespace ProcessMaker\Http\Controllers\Api\Administration;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\Role;
use ProcessMaker\Transformers\RoleTransformer;


/**
 * Controller that handles all Roles API endpoints
 *
 */
class RolesController extends Controller
{

    /**
     * Fetch a collection of roles based on paged request and filter if provided
     * 
     * @return JsonResponse A list of matched roles and paging data
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
            $roles = Role::where('name', 'like', $filter)
                ->orWhere('code', 'like', $filter)
                ->orWhere('description', 'like', $filter)
                ->paginate($perPage);
        } else {
            $roles = Role::paginate($perPage);
        }
        // Return fractal representation of paged data
        return fractal($roles, new RoleTransformer())->respond();
    }

    /**
     * Fetch a single role from the system and return
     */
    public function get(Request $request, Role $role)
    {
        return fractal($role, new RoleTransformer())->respond();
    }

}
