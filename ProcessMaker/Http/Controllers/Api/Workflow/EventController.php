<?php
namespace ProcessMaker\Http\Controllers\Api\Workflow;

use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Task;

class EventController extends Controller
{

    public function trigger(Process $process, Task $event)
    {
        return ['message' => 'OK'];
    }
}