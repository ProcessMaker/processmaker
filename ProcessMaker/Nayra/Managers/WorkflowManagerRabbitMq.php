<?php

namespace ProcessMaker\Nayra\Managers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Contracts\WorkflowManagerInterface;
use ProcessMaker\Facades\MessageBrokerService;
use ProcessMaker\GenerateAccessToken;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Models\Process as Definitions;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\User;
use ProcessMaker\Nayra\Contracts\Bpmn\BoundaryEventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ScriptTaskInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\StartEventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;

class WorkflowManagerRabbitMq extends WorkflowManagerDefault implements WorkflowManagerInterface
{
    const ACTION_START_PROCESS = 'START_PROCESS';
    const ACTION_COMPLETE_TASK = 'COMPLETE_TASK';
    const ACTION_TRIGGER_INTERMEDIATE_EVENT = 'TRIGGER_INTERMEDIATE_EVENT';
    const ACTION_RUN_SCRIPT = 'RUN_SCRIPT';
    const ACTION_TRIGGER_BOUNDARY_EVENT = 'TRIGGER_BOUNDARY_EVENT';
    const ACTION_TRIGGER_MESSAGE_EVENT = 'TRIGGER_MESSAGE_EVENT';

    /**
     * Trigger a start event and return the process request instance.
     *
     * @param Definitions $definitions
     * @param StartEventInterface $event
     * @param array $data
     * @return ProcessRequest
     */
    public function triggerStartEvent(Definitions $definitions, StartEventInterface $event, array $data): ProcessRequest
    {
        // Validate data
        $this->validateData($data, $definitions, $event);

        // Get complementary information
        $version = $definitions->getLatestVersion();
        $userId = $this->getCurrentUserId();

        // Create immediately a new process request
        $request = ProcessRequest::create([
            'process_id' => $definitions->id,
            'user_id' => $userId,
            'callable_id' => $event->getProcess()->getId(),
            'status' => 'ACTIVE',
            'data' => $data,
            'name' => $definitions->name,
            'do_not_sanitize' => [],
            'initiated_at' => Carbon::now(),
            'process_version_id' => $version->getKey(),
            'signal_events' => [],
        ]);

        // Serialize instance
        $state = $this->serializeState($request);

        // Dispatch start process action
        $this->dispatchAction([
            'bpmn' => $version->getKey(),
            'action' => self::ACTION_START_PROCESS,
            'params' => [
                'instance_id' => $request->uuid,
                'request_id' => $request->getKey(),
                'element_id' => $event->getId(),
                'data' => $data,
                'extra_properties' => [
                    'user_id' => $userId,
                    'process_id' => $definitions->id,
                    'request_id' => $request->getKey(),
                ],
            ],
            'state' => $state,
            'session' => [
                'user_id' => $userId,
            ],
        ]);

        //Return the instance created
        return $request;
    }

    /**
     * Complete a task.
     *
     * @param Definitions $definitions
     * @param ExecutionInstanceInterface $instance
     * @param TokenInterface $token
     * @param array $data
     *
     * @return void
     */
    public function completeTask(Definitions $definitions, ExecutionInstanceInterface $instance, TokenInterface $token, array $data)
    {
        // Validate data
        $element = $token->getDefinition(true);
        $this->validateData($data, $definitions, $element);

        // Get complementary information
        $version = $instance->process_version_id;
        $userId = $this->getCurrentUserId();
        $state = $this->serializeState($instance);

        // Dispatch complete task action
        $this->dispatchAction([
            'bpmn' => $version,
            'action' => self::ACTION_COMPLETE_TASK,
            'params' => [
                'request_id' => $token->process_request_id,
                'token_id' => $token->uuid,
                'element_id' => $token->element_id,
                'data' => $data,
            ],
            'state' => $state,
            'session' => [
                'user_id' => $userId,
            ],
        ]);
    }

    /**
     * Complete a catch event.
     *
     * @param Definitions $definitions
     * @param ExecutionInstanceInterface $instance
     * @param TokenInterface $token
     * @param array $data
     *
     * @return void
     */
    public function completeCatchEvent(Definitions $definitions, ExecutionInstanceInterface $instance, TokenInterface $token, array $data)
    {
        // Validate data
        $element = $token->getDefinition(true);
        $this->validateData($data, $definitions, $element);

        // Get complementary information
        $version = $instance->process_version_id;
        $userId = $this->getCurrentUserId();
        $state = $this->serializeState($instance);

        // Dispatch complete task action
        $this->dispatchAction([
            'bpmn' => $version->getKey(),
            'action' => self::ACTION_TRIGGER_INTERMEDIATE_EVENT,
            'params' => [
                'request_id' => $token->process_request_id,
                'token_id' => $token->uuid,
                'element_id' => $token->element_id,
                'data' => $data,
            ],
            'state' => $state,
            'session' => [
                'user_id' => $userId,
            ],
        ]);
    }

    /**
     * Run a script task.
     *
     * @param ScriptTaskInterface $scriptTask
     * @param TokenInterface $token
     */
    public function runScripTask(ScriptTaskInterface $scriptTask, TokenInterface $token)
    {
        // Log execution
        Log::info('Dispatch a script task: ' . $scriptTask->getId() . ' #' . $token->getId());

        // Get complementary information
        $instance = $token->processRequest;
        $version = $instance->process_version_id;
        $userId = $this->getCurrentUserId();
        $state = $this->serializeState($instance);

        // Dispatch complete task action
        $this->dispatchAction([
            'bpmn' => $version,
            'action' => self::ACTION_RUN_SCRIPT,
            'params' => [
                'request_id' => $token->process_request_id,
                'token_id' => $token->uuid,
                'element_id' => $token->element_id,
                'data' => [],
            ],
            'state' => $state,
            'session' => [
                'user_id' => $userId,
            ],
        ]);
    }

    /**
     * Trigger a boundary event
     *
     * @param Definitions $definitions
     * @param ExecutionInstanceInterface $instance
     * @param TokenInterface $token
     * @param BoundaryEventInterface $boundaryEvent
     * @param array $data
     *
     * @return void
     */
    public function triggerBoundaryEvent(
        Definitions $definitions,
        ExecutionInstanceInterface $instance,
        TokenInterface $token,
        BoundaryEventInterface $boundaryEvent,
        array $data
    ) {
        //Validate data
        $this->validateData($data, $definitions, $boundaryEvent);

        // Get complementary information
        $version = $instance->process_version_id;
        $userId = $this->getCurrentUserId();
        $state = $this->serializeState($instance);

        // Dispatch complete task action
        $this->dispatchAction([
            'bpmn' => $version,
            'action' => self::ACTION_TRIGGER_BOUNDARY_EVENT,
            'params' => [
                'request_id' => $token->process_request_id,
                'token_id' => $token->uuid,
                'element_id' => $token->element_id,
                'data' => [],
            ],
            'state' => $state,
            'session' => [
                'user_id' => $userId,
            ],
        ]);
    }

    /**
     * Triggers a message event in the process instance based on provided parameters.
     *
     * @param $instanceId of the process instance that is to be triggered
     * @param $elementId of the catch message event element
     * @param $messageRef of the message event that is to be triggered
     * @param $payload (optional) array of key-value pairs that are to be stored in the data store
     */
    public function throwMessageEvent($instanceId, $elementId, $messageRef, array $payload = [])
    {
        // Get complementary information
        $instance = ProcessRequest::find($instanceId);
        $version = $instance->process_version_id;
        $userId = $this->getCurrentUserId();
        $state = $this->serializeState($instance);

        // Dispatch complete task action
        $this->dispatchAction([
            'bpmn' => $version,
            'action' => self::ACTION_TRIGGER_MESSAGE_EVENT,
            'params' => [
                'instance_id' => $instanceId,
                'element_id' => $elementId,
                'message_ref' => $messageRef,
                'data' => $payload,
            ],
            'state' => $state,
            'session' => [
                'user_id' => $userId,
            ],
        ]);
    }

    /**
     * Build a state object.
     *
     * @param ProcessRequest $instance
     * @return array
     */
    private function serializeState(ProcessRequest $instance)
    {
        // Get open tokens
        $tokensRows = [];
        $tokens = $instance->tokens()->where('status', '!=', 'CLOSED')->where('status', '!=', 'TRIGGERED')->get();
        foreach ($tokens as $token) {
            $tokensRows[] = array_merge($token->token_properties ?: [], [
                'id' => $token->uuid,
                'status' => $token->status,
                'index' => $token->element_index,
                'element_id' => $token->element_id,
            ]);
        }

        return [
            'requests' => [
                [
                    'id' => $instance->uuid,
                    'callable_id' => $instance->callable_id,
                    'data' => $instance->data,
                    'tokens' => $tokensRows,
                ],
            ],
        ];
    }

    /**
     * Get the ID of the currently authenticated user.
     *
     * @return int|null
     */
    private function getCurrentUserId(): ? int
    {
        // Get the id from the current user
        $webGuardId = Auth::id();
        $apiGuardId = Auth::guard('api')->id();

        return $webGuardId ?? $apiGuardId;
    }

    /**
     * Get the ID of the currently authenticated user.
     *
     * @return int|null
     */
    private function getCurrentUser(): ? User
    {
        // Get the id from the current user
        $webGuard = Auth::user();
        $apiGuard = Auth::guard('api')->user();

        return $webGuard ?: $apiGuard;
    }

    /**
     * Send payload
     *
     * @param array $action
     */
    private function dispatchAction(array $action): void
    {
        // add environment variables to session
        $environmentVariables = $this->getEnvironmentVariables();
        $action['session']['env'] = $environmentVariables;
        $subject = 'requests';
        $thread = $action['collaboration_id'] ?? 0;
        MessageBrokerService::sendMessage($subject, $thread, $action);
    }

    /**
     * Get the environment variables.
     *
     * @return array
     */
    private function getEnvironmentVariables()
    {
        $variablesParameter = [];
        EnvironmentVariable::chunk(50, function ($variables) use (&$variablesParameter) {
            foreach ($variables as $variable) {
                $variablesParameter[$variable['name']] = $variable['value'];
            }
        });

        // Add the url to the host
        $variablesParameter['HOST_URL'] = config('app.docker_host_url');

        $user = $this->getCurrentUser();
        if ($user) {
            $token = new GenerateAccessToken($user);
            $environmentVariables['API_TOKEN'] = $token->getToken();
            $environmentVariables['API_HOST'] = config('app.url') . '/api/1.0';
            $environmentVariables['APP_URL'] = config('app.url');
            $environmentVariables['API_SSL_VERIFY'] = (config('app.api_ssl_verify') ? '1' : '0');
        }

        return $variablesParameter;
    }
}
