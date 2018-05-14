<?php

namespace ProcessMaker\Http\Controllers\Api\Designer;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use ProcessMaker\Exception\DoesNotBelongToProcessException;
use ProcessMaker\Facades\TriggerManager;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Trigger;
use Symfony\Component\HttpFoundation\Response;

class TriggerController extends Controller
{
    /**
     * Get a list of triggers in a project.
     *
     * @param Process $process
     *
     * @return ResponseFactory|Response
     */
    public function index(Process $process)
    {
        $response = TriggerManager::index($process);
        return response($response, 200);
    }

    /**
     * Get a single trigger in a project.
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
        return response($trigger->toArray(), 200);
    }

    /**
     * Create a new trigger in a project.
     *
     * @param Process $process
     * @param Request $request
     *
     * @return ResponseFactory|Response
     */
    public function store(Process $process, Request $request)
    {
        $data = [
            'title' =>  $request->input('tri_title', ''),
            'description' =>  $request->input('tri_description', ''),
            'webbot' =>  $request->input('tri_webbot', ''),
            'param' =>  $request->input('tri_param', '')
        ];

        $response = TriggerManager::save($process, $data);
        return response($response, 201);
    }

    /**
     * Update a trigger in a project.
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
        if ($request->has('tri_title')) {
            $data['title'] = $request->input('tri_title');
        }
        if ($request->has('tri_description')) {
            $data['description'] = $request->input('tri_description');
        }
        if ($request->has('tri_webbot')) {
            $data['webbot'] = $request->input('tri_webbot');
        }
        if ($request->has('tri_param')) {
            $data['param'] = $request->input('tri_param');
        }
        if($data) {
            TriggerManager::update($process, $trigger, $data);
        }
        return response([], 200);
    }

    /**
     * Delete a trigger in a project.
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
    private function belongsToProcess(Process $process, Trigger $trigger): void
    {
        if($process->id !== $trigger->process_id) {
            Throw new DoesNotBelongToProcessException(__('The trigger does not belong to this process.'));
        }
    }

}
