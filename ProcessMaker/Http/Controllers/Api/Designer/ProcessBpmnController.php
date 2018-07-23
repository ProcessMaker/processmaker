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
        return ($process->bpmn);
    }

    /**
     * Updates the bpmn definition of a process
     *
     * @param Request $request
     * @param Process $process
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function update(Request $request, Process $process)
    {
        $process->makeVisible(['bpmn']);
        $process->bpmn = $request->get('bpmn');
        ProcessManager::update($process, [
            'bpmn' => $request->get('bpmn')
        ]);
        return response([], 200);
    }
}
