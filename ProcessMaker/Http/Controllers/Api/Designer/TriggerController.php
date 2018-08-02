<?php

namespace ProcessMaker\Http\Controllers\Api\Designer;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use ProcessMaker\Exception\DoesNotBelongToProcessException;
use ProcessMaker\Facades\TriggerManager;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Trigger;
use ProcessMaker\Transformers\TriggerTransformer;
use Symfony\Component\HttpFoundation\Response;

class TriggerController extends Controller
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
            'current_page' => $request->input('current_page', 1),
            'per_page' => $request->input('per_page', 10),
            'sort_by' => $request->input('order_by', 'title'),
            'sort_order' => $request->input('order_direction', 'ASC'),
        ];
        $response = TriggerManager::index($process, $options);
        return fractal($response, new TriggerTransformer())->respond();
    }

    /**
     * Get a single trigger in a process.
     *
     * @param Process $process
     * @param Trigger $trigger
     *
     * @return ResponseFactory|Response
     * @throws DoesNotBelongToProcessException
     */
    public function show(Process $process, Trigger $trigger)
    {
        $this->belongsToProcess($process, $trigger);
        return fractal($trigger, new TriggerTransformer())->respond(200);
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

        $response = TriggerManager::save($process, $data);
        return fractal($response, new TriggerTransformer())->respond(201);
    }

    /**
     * Update a trigger in a process.
     *
     * @param Process $process
     * @param Trigger $trigger
     * @param Request $request
     *
     * @return ResponseFactory|Response
     * @throws DoesNotBelongToProcessException
     */
    public function update(Process $process, Trigger $trigger, Request $request)
    {
        $this->belongsToProcess($process, $trigger);
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
            TriggerManager::update($process, $trigger, $data);
        }
        return response([], 204);
    }

    /**
     * Delete a trigger in a process.
     *
     * @param Process $process
     * @param Trigger $trigger
     *
     * @return ResponseFactory|Response
     * @throws DoesNotBelongToProcessException
     */
    public function remove(Process $process, Trigger $trigger)
    {
        $this->belongsToProcess($process, $trigger);
        TriggerManager::remove($trigger);
        return response([], 204);
    }

    /**
     * Validate if trigger belong to process.
     *
     * @param Process $process
     * @param Trigger $trigger
     *
     * @throws DoesNotBelongToProcessException|void
     */
    private function belongsToProcess(Process $process, Trigger $trigger)
    {
        if($process->id !== $trigger->process_id) {
            Throw new DoesNotBelongToProcessException(__('The trigger does not belong to this process.'));
        }
    }

}
