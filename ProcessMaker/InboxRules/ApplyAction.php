<?php

namespace ProcessMaker\InboxRules;

use Facades\ProcessMaker\InboxRules\MatchingTasks;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\SanitizeHelper;
use ProcessMaker\Http\Resources\Task as Resource;

class ApplyAction
{
    public function applyActionOnTask(ProcessRequestToken $task){
        $array = [
                "id" => 14,
                "name" => "ullam",
                "user_id" => 56,
                "active" => 1,
                "end_date" => null,
                "saved_search_id" => null,
                "process_request_token_id" => 10,
                "mark_as_priority" => 0,
                "reassign_to_user_id" => null,
                "fill_data" => 0,
                "submit_data" => null,
                "created_at" => "2024-02-20 03:01:41",
                "updated_at" => "2024-02-20 03:01:41"
        ];
        $matchingTasks = MatchingTasks::matchingInboxRules($task);


        foreach ($matchingTasks as $inputRule) {
        
            //If $savedSearchId is null For Task Rules only
            if($inputRule->saved_search_id === null) {
                
                //Fill and save as draft
                if($inputRule->fill_data === true) {
                    //here process for draft
                }
                //Submit the form
                if($inputRule->submit_data !== null) {
                    if ($task->status === 'CLOSED') {
                        return abort(422, __('Task already closed'));
                    }
                    // Skip ConvertEmptyStringsToNull and TrimStrings middlewares
                    $data = $inputRule->submit_data;
                    $data = SanitizeHelper::sanitizeData($data['data'], null, $task->processRequest->do_not_sanitize ?? []);
                    //Call the manager to trigger the start event
                    $process = $task->process;
                    $instance = $task->processRequest;
                    WorkflowManager::completeTask($process, $instance, $task, $data);
        
                    return new Resource($task->refresh());
                }

            } else {
                //Mark as priority
                if ($inputRule->mark_as_priority === true) {
                    ProcessRequestToken::where('process_request_token_id', $inputRule->process_request_token_id)
                    ->update(['is_priority' => 1]);
                }

                //Reassign to user id
                if ($inputRule->reassign_to_user_id !== null) {
                    $task->authorizeReassignment(Auth::user());
                    // Reassign user
                    $task->reassignTo($inputRule->reassign_to_user_id);
                    $task->persistUserData($inputRule->reassign_to_user_id);
                    $task->save();
                }
            }
        }


    }
}
