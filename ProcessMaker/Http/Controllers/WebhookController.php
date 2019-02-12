<?php

namespace ProcessMaker\Http\Controllers;

use ProcessMaker\Http\Controllers\Controller;
use Illuminate\Http\Request;
use ProcessMaker\Models\ProcessWebhook;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Http\Resources\ProcessRequests as ProcessRequestsResource;

class WebhookController extends Controller
{
    function startEvent(Request $request)
    {
        $webhook = ProcessWebhook::where([
            'token' => $request->input('token')
        ])->first();

        if (!$webhook) {
            return response('Invalid Token', 404);
        }

        $definitions = $webhook->process->getDefinitions();
        if (!$definitions->findElementById($webhook->node)) {
            return abort(404);
        }

        $event = $definitions->getEvent($webhook->node);
        $data = $request->post();

        //Trigger the start event
        $processRequest = WorkflowManager::triggerStartEvent($webhook->process, $event, $data);
        return new ProcessRequestsResource($processRequest);
    }
}