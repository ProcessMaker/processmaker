<?php

namespace ProcessMaker\Http\Controllers\Api\Workflow;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Task;


/**
 *
 */
class EventController extends Controller
{
    public function trigger(Process $process, Task $event)
    {
        return [
            'process' => $process,
            'start_event' => $event
        ];
    }
}
