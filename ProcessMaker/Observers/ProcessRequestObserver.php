<?php

namespace ProcessMaker\Observers;

use ProcessMaker\Events\RequestAction;
use ProcessMaker\Events\RequestError;
use ProcessMaker\Managers\DataManager;
use ProcessMaker\Models\CaseNumber;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ScheduledTask;

class ProcessRequestObserver
{
    /**
     * Handle the process request "deleting" event.
     *
     * @param  ProcessRequest  $request
     * @return void
     */
    public function deleting(ProcessRequest $request)
    {
        //A Scheduled Task delete child requests in cascade
        ScheduledTask::where('process_request_id', $request->id)
            ->delete();

        //A Scheduled Task delete child requests in cascade
        ProcessRequestToken::where('process_request_id', $request->id)
            ->delete();
        //A Process Request Token delete child requests in cascade
        ProcessRequestToken::where('subprocess_request_id', $request->id)
            ->delete();
        // Delete all tokens for subprocesses
        $childRequests = ProcessRequest::where('parent_request_id', $request->id)->get();
        foreach ($childRequests as $child) {
            $child->delete();
        }
        //A request delete child requests in cascade
        ProcessRequest::where('parent_request_id', $request->id)
            ->delete();
    }

    /**
     * Handle the process request "saved" event.
     *
     * @param  ProcessRequest  $request
     * @return void
     */
    public function saved(ProcessRequest $request)
    {
        if ($request->status === 'ERROR') {
            $errors = $request->getAttribute('errors') ?? [];
            foreach ($errors as $error) {
                RequestError::dispatchIfNotRateLimited($request, $error['message']);
            }
        }
        if ($request->status === 'COMPLETED') {
            event(new RequestAction($request, RequestAction::ACTION_COMPLETED));
            // Remove scheduled tasks for this request
            $request->scheduledTasks()->delete();
        }
    }

    public function saving(ProcessRequest $request)
    {
        // When data is updated we update the case_title
        if ($request->isDirty('data')) {
            $dm = new DataManager();
            $data = $dm->getRequestData($request);
            $data = $dm->updateRequestMagicVariable($data, $request);
            // If request is a parent process, inherit the case title to the child requests
            if (!$request->parent_request_id) {
                $mustacheTitle = $request->getCaseTitleFromProcess();
                $request->case_title = $request->evaluateCaseTitle($mustacheTitle, $data, false);
                $request->case_title_formatted = $request->evaluateCaseTitle($mustacheTitle, $data, true);
                // Copy the case title to the child requests
                if (!empty($request->id)) {
                    ProcessRequest::where('parent_request_id', $request->id)
                        ->update([
                            'case_title' => $request->case_title,
                            'case_title_formatted' => $request->case_title_formatted,
                        ]);
                }
            } else {
                // If request is a subprocess, inherit the case title from the parent
                $parent = ProcessRequest::whereId($request->parent_request_id)
                    ->select('case_title', 'case_title_formatted')
                    ->first();
                $request->case_title = $parent->case_title;
                $request->case_title_formatted = $parent->case_title_formatted;
            }
        }
    }

    public function created(ProcessRequest $request)
    {
        // If request is System, don't generate a case number
        if ($request->isSystem()) {
            return;
        }
        // If request is a subprocess, inherit the case number from the parent
        if ($request->parent_request_id) {
            $request->case_number = ProcessRequest::whereId($request->parent_request_id)
                ->select('case_number')
                ->first()
                ->case_number;

            $request->save();

            return;
        }
        // If request is not a subprocess and not a system process, generate a case number
        $request->case_number = CaseNumber::generate($request->id);
        $request->save();
    }
}
