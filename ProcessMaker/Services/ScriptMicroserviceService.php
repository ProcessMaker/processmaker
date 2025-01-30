<?php

namespace ProcessMaker\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use ProcessMaker\Events\ScriptResponseEvent;
use ProcessMaker\Exception\ScriptException;
use ProcessMaker\Exception\ScriptTimeoutException;
use ProcessMaker\Jobs\CompleteActivity;
use ProcessMaker\Jobs\ErrorHandling;
use ProcessMaker\Jobs\RunScriptTask;
use ProcessMaker\Models\Process as Definitions;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\User;
use ProcessMaker\Nayra\Contracts\Bpmn\ScriptTaskInterface;
use ProcessMaker\Notifications\ErrorExecutionNotification;
use Throwable;

class ScriptMicroserviceService
{
    public function handle(Request $request)
    {
        $response = $request->all();
        Log::debug('Response microservice executor: ' . print_r($response, true));
        // If the call is from preview
        if (!empty($response['metadata']['nonce'])) {
            $status = $response['status'] === 'success' ? 200 : 500;
            $output = $response['status'] === 'success'
                ? ['output' => $response['output']]
                : $response;

            event(new ScriptResponseEvent(
                User::find($response['metadata']['current_user']),
                $status,
                $output,
                null,
                $response['metadata']['nonce']));
        }
        if (!empty($response['metadata']['script_task'])) {
            $script = Script::find($response['metadata']['script_task']['script_id']);
            $definition = Definitions::find($response['metadata']['script_task']['definition_id']);
            $instance = ProcessRequest::find($response['metadata']['script_task']['instance_id']);
            $token = ProcessRequestToken::find($response['metadata']['script_task']['token_id']);
            $token = $token->loadTokenInstance();
            if ($response['status'] === 'success') {
                CompleteActivity::dispatch($definition, $instance, $token, $response['output'])->onQueue('bpmn');
            } elseif ($response['status'] === 'error') {
                try {
                    if (str_starts_with($response['message'], 'Command exceeded timeout of')) {
                        throw new ScriptTimeoutException($response['message']);
                    }
                    throw new ScriptException($response['message']);
                } catch (Throwable $e) {
                    $message = $e->getMessage();
                    $finalAttempt = true;
                    $definitions = $definition
                        ->getPublishedVersion($response['metadata']['script_task']['data'] ?: [])
                        ->getDefinitions();
                    $element = $definitions->getElementInstanceById($response['metadata']['script_task']['element_id']);

                    $job = new RunScriptTask(
                        $definition,
                        $instance,
                        $token,
                        $response['metadata']['script_task']['data'],
                        (int) $response['metadata']['script_task']['attempts'],
                    );

                    $errorHandling = new ErrorHandling($element, $token);
                    $errorHandling->setDefaultsFromScript($script->versionFor($instance));
                    [$message, $finalAttempt] = $errorHandling->handleRetries($job, $e);

                    if ($finalAttempt) {
                        $token->setStatus(ScriptTaskInterface::TOKEN_STATE_FAILING);
                        $token->save();
                    }

                    $error = $element->getRepository()->createError();
                    $error->setName($message);

                    $token->setProperty('error', $error);
                    $token->logError($e, $element);

                    Log::error('Script failed: ' . $script->id . ' - ' . $message);
                    //Log::error($exception->getTraceAsString());
                }
            }
        }
    }
}
