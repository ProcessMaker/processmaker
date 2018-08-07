<?php

namespace ProcessMaker\Http\Controllers\Api\Designer;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use ProcessMaker\Exception\DoesNotBelongToProcessException;
use ProcessMaker\Facades\ScriptManager;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Script;
use ProcessMaker\Transformers\ScriptTransformer;
use Symfony\Component\HttpFoundation\Response;

class ScriptController extends Controller
{
    /**
     * Get a list of triggers in a process.
     *
     * @param Process $process
     * @param Request $request
     *
     * @return ResponseFactory|Response
     */
    public function index(Process $process, Request $request)
    {
        $options = [
            'filter' => $request->input('filter', ''),
            'current_page' => $request->input('page', 1),
            'per_page' => $request->input('per_page', 10),
            'sort_by' => $request->input('order_by', 'title'),
            'sort_order' => $request->input('order_direction', 'ASC'),
        ];
        $response = ScriptManager::index($process, $options);
        return fractal($response, new ScriptTransformer())->respond();
    }

    /**
     * Get a single trigger in a process.
     *
     * @param Process $process
     * @param Script $script
     *
     * @return ResponseFactory|Response
     * @throws DoesNotBelongToProcessException
     */
    public function show(Process $process, Script $script)
    {
        $this->belongsToProcess($process, $script);
        return fractal($script, new ScriptTransformer())->respond(200);
    }

    /**
     * Create a new trigger in a process.
     *
     * @param Process $process
     * @param Request $request
     *
     * @return ResponseFactory|Response
     */
    public function store(Process $process, Request $request)
    {
        $data = [
            'title' =>  $request->input('title', ''),
            'description' =>  $request->input('description', ''),
            'webbot' =>  $request->input('webbot', ''),
            'param' =>  $request->input('param', '')
        ];

        $response = ScriptManager::save($process, $data);
        return fractal($response, new ScriptTransformer())->respond(201);
    }

    /**
     * Update a trigger in a process.
     *
     * @param Process $process
     * @param Script $script
     * @param Request $request
     *
     * @return ResponseFactory|Response
     * @throws DoesNotBelongToProcessException
     */
    public function update(Process $process, Script $script, Request $request)
    {
        $this->belongsToProcess($process, $script);
        $data = [];
        if ($request->has('title')) {
            $data['title'] = $request->input('title');
        }
        if ($request->has('description')) {
            $data['description'] = $request->input('description');
        }
        if ($request->has('webbot')) {
            $data['webbot'] = $request->input('webbot');
        }
        if ($request->has('param')) {
            $data['param'] = $request->input('param');
        }
        if($data) {
            ScriptManager::update($process, $script, $data);
        }
        return response([], 204);
    }

    /**
     * Delete a trigger in a process.
     *
     * @param Process $process
     * @param Script $script
     *
     * @return ResponseFactory|Response
     * @throws DoesNotBelongToProcessException
     */
    public function remove(Process $process, Script $script)
    {
        $this->belongsToProcess($process, $script);
        ScriptManager::remove($script);
        return response([], 204);
    }

    /**
     * Validate if trigger belong to process.
     *
     * @param Process $process
     * @param Script $script
     *
     * @throws DoesNotBelongToProcessException|void
     */
    private function belongsToProcess(Process $process, Script $script)
    {
        if($process->id !== $script->process_id) {
            Throw new DoesNotBelongToProcessException(__('The trigger does not belong to this process.'));
        }
    }

}
