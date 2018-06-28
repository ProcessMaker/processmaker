<?php
namespace ProcessMaker\Http\Controllers\Api\Workflow;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\Process;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Model\Application as Instance;
use ProcessMaker\Model\Delegation as Token;

class ActivityController extends Controller
{

    public function complete(Request $request, Process $process, Instance $instance, Token $token)
    {
        $data = (array) $request->json();

        //Call the manager to trigger the start event
        WorkflowManager::completeTask($process, $instance, $token, $data);
        return ['message' => 'OK'];
    }
}
