<?php
namespace ProcessMaker\Http\Controllers\Api\Workflow;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Task;
use ProcessMaker\Facades\WorkflowManager;

class EventController extends Controller
{

    public function triggerStart(Request $request, Process $process, $eventId)
    {
        //Get required references
        $definitions = $process->getDefinitions();
        $event = $definitions->getEvent($eventId);
        $data = (array) $request->json();

        //Call the manager to trigger the start event
        $instance = WorkflowManager::triggerStartEvent($process, $event, $data);
        return ['instance' => $instance->uid];
    }

    public function callProcess(Request $request, Process $process, $processId)
    {
        //Get required references
        $definitions = $process->getDefinitions();
        $calledProcess = $definitions->getProcess($processId);
        $data = (array) $request->json();

        //Call the manager to trigger the start event
        $instance = WorkflowManager::callProcess($process, $calledProcess, $data);
        return ['instance' => $instance->uid];
    }

    public function triggerIntermediate(Process $process, Task $event)
    {
        return ['message' => 'OK'];
    }
}
