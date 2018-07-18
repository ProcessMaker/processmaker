<?php

namespace ProcessMaker\Http\Controllers\Api\Designer;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ProcessMaker\Facades\ProcessManager;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\Process;
use ProcessMaker\Transformers\ProcessBpmnTransformer;

/**
 * Implements endpoints to manage the processes.
 *
 */
class ProcessBpmnController extends Controller
{
    /**
     * Returns the bpmn definition of a process
     *
     * @param \ProcessMaker\Model\Process $process
     *
     * @return ResponseFactory|Response
     */
    public function show(Process $process)
    {
        $process->makeVisible(['bpmn']);

        //return fractal($process->bpmn, new ProcessBpmnTransformer())->respond();
        return ($process->bpmn);
    }
}
