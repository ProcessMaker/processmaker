<?php
namespace ProcessMaker\Http\Controllers\Api\Workflow;

use Illuminate\Http\Request;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\Application;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Task;
use ProcessMaker\Transformers\ApplicationTransformer;

class EventController extends Controller
{

    public function triggerStart(Request $request, Process $process, $eventId)
    {
        //Get required references
        $definitions = $process->getDefinitions();
        $event = $definitions->getEvent($eventId);
        $data = $request->input();

        //Call the manager to trigger the start event
        $instance = WorkflowManager::triggerStartEvent($process, $event, $data);
        return fractal($instance, new ApplicationTransformer())->respond();
    }

    public function callProcess(Request $request, Process $process, $processId)
    {
        //Get required references
        $definitions = $process->getDefinitions();
        $calledProcess = $definitions->getProcess($processId);
        $data = $request->input();

        //Call the manager to trigger the start event
        $instance = WorkflowManager::callProcess($process, $calledProcess, $data);
        return fractal($instance, new ApplicationTransformer())->respond();
    }

    public function triggerIntermediate(Process $process, Task $event)
    {
        return ['message' => 'OK'];
    }
}
