<?php

namespace ProcessMaker\Http\Controllers\Api\Designer;

use Illuminate\Contracts\Routing\ResponseFactory;
use ProcessMaker\Exception\TriggerException;
use ProcessMaker\Facades\TriggerManager;
use ProcessMaker\Http\Controllers\Controller;
use Illuminate\Http\Request;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Triggers;
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
        try
        {
            $response = TriggerManager::getTriggers($process);
            return response($response, 200);
        } catch (TriggerException $exception) {
            return response($exception->getMessage(), $exception->getCode() ?: 400);
        }
    }

    /**
     * Get a single trigger in a project.
     *
     * @param Process $process
     * @param Triggers $trigger
     *
     * @return ResponseFactory|Response
     */
    public function show(Process $process, Triggers $trigger)
    {
        try
        {
            $response = TriggerManager::getTriggers($process);
            return response($response, 200);
        } catch (TriggerException $exception) {
            return response($exception->getMessage(), $exception->getCode() ?: 400);
        }
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
        try
        {
            $data = [
                'TRI_TITLE' =>  $request->input('tri_title', ''),
                'TRI_DESCRIPTION' =>  $request->input('tri_description', ''),
                'TRI_WEBBOT' =>  $request->input('tri_webbot', ''),
                'TRI_PARAM' =>  $request->input('tri_param', '')
            ];

            $response = TriggerManager::save($process, $data);
            return response($response, 201);
        } catch (TriggerException $exception) {
            return response($exception->getMessage(), $exception->getCode() ?: 400);
        }
    }

    /**
     * Update a trigger in a project.
     *
     * @param Process $process
     * @param Triggers $trigger
     * @param Request $request
     *
     * @return ResponseFactory|Response
     */
    public function update(Process $process, Triggers $trigger, Request $request)
    {
        try
        {
            $response = TriggerManager::getTriggers($process);
            return response([], 200);
        } catch (TriggerException $exception) {
            return response($exception->getMessage(), $exception->getCode() ?: 400);
        }
    }

    /**
     * Delete a trigger in a project.
     *
     * @param Process $process
     * @param Triggers $trigger
     *
     * @return ResponseFactory|Response
     */
    public function remove(Process $process, Triggers $trigger)
    {
        try
        {
            $response = TriggerManager::getTriggers($process);
            return response($response, 200);
        } catch (TriggerException $exception) {
            return response($exception->getMessage(), $exception->getCode() ?: 400);
        }
    }

}