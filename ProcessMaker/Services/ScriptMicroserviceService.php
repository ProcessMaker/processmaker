<?php

namespace ProcessMaker\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Events\ScriptResponseEvent;
use ProcessMaker\Exception\ScriptException;
use ProcessMaker\Exception\ScriptTimeoutException;
use ProcessMaker\Jobs\CheckScriptTimeout;
use ProcessMaker\Jobs\CompleteActivity;
use ProcessMaker\Jobs\ErrorHandling;
use ProcessMaker\Jobs\RunScriptTask;
use ProcessMaker\Models\Process as Definitions;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\User;

class ScriptMicroserviceService
{
    public function handle(Request $request)
    {
        $response = $request->all();
        Log::debug('Response microservice executor: ' . print_r($response, true));
        // If the call is from preview
        if ($response['metadata']['nonce']) {
            $status = $response['status'] === 'success' ? 200 : 500;
            $output = $response['status'] === 'success'
                ? ['output' => $response['output']]
                : $response;

            event(new ScriptResponseEvent(
                User::find($response['metadata']['user_id']),
                $status,
                $output,
                null,
                $response['metadata']['nonce']));
        }

        if ($response['metadata']['execution_id']) {
            CheckScriptTimeout::stop($response['metadata']['execution_id']);
        }

        if (!empty($response['metadata']['script_task'])) {
            $script = Script::find($response['metadata']['script_task']['script_id']);
            $definitions = Definitions::find($response['metadata']['script_task']['definition_id']);
            $instance = ProcessRequest::find($response['metadata']['script_task']['instance_id']);
            $token = ProcessRequestToken::find($response['metadata']['script_task']['token_id']);
            if ($response['status'] === 'success') {
                CompleteActivity::dispatch($definitions, $instance, $token, $response['output'])->onQueue('bpmn');
            }
        }

        try {
            ErrorHandling::convertErrorResponseToException($response);
        } catch (ScriptException|ScriptTimeoutException $e) {
            $element = $definitions->getDefinitions(true)->getElementInstanceById($token->element_id);
            $errorHandling = new ErrorHandling($element, $token);
            $errorHandling->setDefaultsFromScript($script->versionFor($instance));
            [$message, $finalAttempt] = $errorHandling->handleRetriesForScriptMicroservice($e, $response['metadata']);
            RunScriptTask::setErrorStatus($finalAttempt, $token, $element, $message, $script->id, $e, true);
        }
    }
}
