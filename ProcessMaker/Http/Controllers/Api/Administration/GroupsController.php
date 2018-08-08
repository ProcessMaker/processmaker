<?php

namespace ProcessMaker\Http\Controllers\Api\Administration;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\Group;
use ProcessMaker\Transformers\GroupTransformer;


/**
 * Controller that handles all Groups API endpoints
 *
 */
class GroupsController extends Controller
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
        $orderBy = $request->input('order_by', 'title');
        $orderDirection = $request->input('order_direction', 'asc');
        // Note, the current page is automatically handled by Laravel's pagination feature
        if($filter) {
            $filter = '%' . $filter . '%';
            $groups = Group::where('title', 'like', $filter)
                ->orderBy($orderBy, $orderDirection);
            if($orderBy == 'total_users') {
                // Need to include number of users associations with this group
                $groups = $groups->leftJoin('group_users', 'group_users.group_id', 'groups.id')
                    ->groupBy('groups.id')
                    ->select(DB::raw('groups.*, count(group_users.id) as total_users'));
            }
            $groups = $groups->paginate($perPage);
        } else {
            if($orderBy == 'total_users') {
                // Need to include users count with roles
                $groups = Group::leftJoin('group_users', 'group_users.group_id', 'groups.id')
                    ->groupBy('groups.id')
                    ->select(DB::raw('groups.*, count(group_users.id) as total_users'))
                    ->orderBy($orderBy, $orderDirection)->paginate($perPage);
            } else {
                $groups = Group::orderBy($orderBy, $orderDirection)->paginate($perPage);
            }
        }
        // Return fractal representation of paged data
        return fractal($groups, new GroupTransformer())->respond();
    }

    /**
     * Fetch a single role from the system and return
     */
    public function get(Request $request, Group $group)
    {
        return fractal($group, new GroupTransformer())->respond();
    }

    public function create(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|max:255|unique:groups,title',
            'status' => 'required|in:ACTIVE,INACTIVE'
        ]);
        $group = Group::create($data);
        //$group->refresh();
        return fractal($group, new GroupTransformer())->respond();
    }

}
