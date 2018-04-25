<?php

namespace ProcessMaker\Http\Controllers\Api\Designer;

class InputDocumentController
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
            'TRI_TITLE' =>  $request->input('tri_title', ''),
            'TRI_DESCRIPTION' =>  $request->input('tri_description', ''),
            'TRI_WEBBOT' =>  $request->input('tri_webbot', ''),
            'TRI_PARAM' =>  $request->input('tri_param', '')
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
            $data['TRI_TITLE'] = $request->input('tri_title');
        }
        if ($request->has('tri_description')) {
            $data['TRI_DESCRIPTION'] = $request->input('tri_description');
        }
        if ($request->has('tri_webbot')) {
            $data['TRI_WEBBOT'] = $request->input('tri_webbot');
        }
        if ($request->has('tri_param')) {
            $data['TRI_PARAM'] = $request->input('tri_param');
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
        return response([], 200);
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
        if($process->PRO_ID !== $trigger->PRO_ID) {
            Throw new DoesNotBelongToProcessException(__('The trigger does not belong to this process.'));
        }
    }

}