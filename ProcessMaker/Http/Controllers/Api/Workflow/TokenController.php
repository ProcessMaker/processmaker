<?php
namespace ProcessMaker\Http\Controllers\Api\Workflow;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\Delegation;
use ProcessMaker\Model\Application;
use ProcessMaker\Model\Process;
use ProcessMaker\Transformers\DelegationTransformer;


class TokenController extends Controller
{
    public function index(Process $process, Application $instance, Request $request)
    {
        // Grab pagination data
        $perPage = $request->input('per_page', 10);
        // Filter
        $filter = $request->input('filter', null);
        $orderBy = $request->input('order_by', 'title');
        $orderDirection = $request->input('order_direction', 'asc');

        $delegations = Delegation::with('user')->where('application_id', $instance->id)
            ->where('thread_status', $request->get('thread_status'))
            ->paginate($perPage);

       // Return fractal representation of paged data
        return fractal($delegations, new DelegationTransformer())->respond();
    }
}
