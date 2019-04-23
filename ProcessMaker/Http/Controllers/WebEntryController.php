<?php

namespace ProcessMaker\Http\Controllers;

use ProcessMaker\Http\Controllers\Controller;
use Illuminate\Http\Request;
use ProcessMaker\Models\ProcessWebEntry;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Http\Resources\ProcessRequests as ProcessRequestsResource;

class WebEntryController extends Controller
{
    function startEvent(Request $request)
    {
        $web_entry = ProcessWebEntry::where([
            'token' => $request->input('token')
        ])->first();

        if (!$web_entry) {
            return response('Invalid Token', 404);
        }

        $definitions = $web_entry->process->getDefinitions();
        if (!$definitions->findElementById($web_entry->node)) {
            return abort(404);
        }

        $event = $definitions->getEvent($web_entry->node);
        $data = $request->post();

        //Trigger the start event
        $processRequest = WorkflowManager::triggerStartEvent($web_entry->process, $event, $data);

        if ($request->header('accept') !== null && strcasecmp($request->header('accept'), 'application/json') === 0) {
            return new ProcessRequestsResource($processRequest);
        }
        else {
            return view('web_entry.success');
        }
    }
}