<?php

namespace ProcessMaker\Managers;

use CURLFile;
use DOMDocument;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use ProcessMaker\Contracts\ServiceTaskImplementationInterface;
use ProcessMaker\Events\ProcessCompleted;
use ProcessMaker\Events\ProcessUpdated;
use ProcessMaker\Jobs\BoundaryEvent;
use ProcessMaker\Jobs\CallProcess;
use ProcessMaker\Jobs\CatchEvent;
use ProcessMaker\Jobs\CompleteActivity;
use ProcessMaker\Jobs\RunScriptTask;
use ProcessMaker\Jobs\RunServiceTask;
use ProcessMaker\Jobs\ThrowMessageEvent;
use ProcessMaker\Jobs\ThrowSignalEvent;
use ProcessMaker\Models\FormalExpression;
use ProcessMaker\Models\Process as Definitions;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ProcessRequestToken as Token;
use ProcessMaker\Models\ProcessVersion;
use ProcessMaker\Models\Script;
use ProcessMaker\Nayra\Contracts\Bpmn\BoundaryEventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\EntityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\EventDefinitionInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ProcessInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ScriptTaskInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ServiceTaskInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\StartEventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ThrowEventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;
use ProcessMaker\Nayra\Storage\BpmnDocument;
use ProcessMaker\Providers\WorkflowServiceProvider;

class WorkflowManager
{
    /**
     * Attached validation callbacks
     *
     * @var array
     */
    protected $validations = [];

    /**
     * Data Validator
     *
     * @var \Illuminate\Contracts\Validation\Validator
     */
    protected $validator;

    /**
     * Service Task implementations
     *
     * @var array
     */
    protected $serviceTaskImplementations = [];

    /**
     * Complete a task.
     *
     * @param Definitions $definitions
     * @param ProcessRequest $instance
     * @param ProcessRequestToken $token
     * @param array $data
     *
     * @return void
     */
    public function completeTask(Definitions $definitions, ProcessRequest $instance, ProcessRequestToken $token, array $data)
    {
        //Validate data
        /*$element = $token->getDefinition(true);
        $this->validateData($data, $definitions, $element);
        CompleteActivity::dispatchNow($definitions, $instance, $token, $data);*/
        $version = $instance->processVersion;
        $deploy_name = $this->getDeployName($definitions, $version);
        $tokensRows = [];
        $tokens = $instance->tokens()->where('status', '!=', 'CLOSED')->get();
        foreach ($tokens as $token) {
            $tokensRows[] = array_merge($token->token_properties ?: [], [
                'id' => $token->getKey(),
                'status' => $token->status,
                'index' => $token->element_index,
                'element_ref' => $token->element_id,
            ]);
        }
        $this->action([
            "bpmn" => $deploy_name,
            "action" => 'COMPLETE_TASK',
            "params" => [
                "request_id" => $token->process_request_id,
                "token_id" => $token->id,
                "element_id" => $token->element_id,
                "data"=> $data,
            ],
            'state' => [
                'requests' => [
                    [
                        'id' => $instance->id,
                        'callable_id' => $instance->callable_id,
                        'data' => $instance->data,
                        'tokens' => $tokensRows,
                    ]
                ]
            ]
        ]);
    }

    /**
     * Complete a catch event
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
        //Validate data
        $element = $token->getDefinition(true);
        $this->validateData($data, $definitions, $element);
        CatchEvent::dispatchNow($definitions, $instance, $token, $data);
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
        BoundaryEvent::dispatchNow($definitions, $instance, $token, $boundaryEvent, $data);
    }

    /**
     * Trigger an start event and return the instance.
     *
     * @param Definitions $definitions
     * @param StartEventInterface $event
     *
     * @return \ProcessMaker\Models\ProcessRequest
     */
    public function triggerStartEvent(Definitions $definitions, StartEventInterface $event, array $data, bool $async = false)
    {
        $t0 = microtime(true);
        //Validate data
        $this->validateData($data, $definitions, $event);
        //Schedule BPMN Action
        $version = $definitions->getLatestVersion();
        $deploy_name = $this->deploy($definitions, $version);
        $request = $this->action([
            "bpmn" => $deploy_name,
            "action" => 'START_PROCESS',
            "params" => [
                "element_id" => $event->getId(),
                "data"=> $data,
            ]
        ], [
            'process_id' => $definitions->id,
            'user_id' => Auth::id(),
            'callable_id' => $event->getProcess()->getId(),
            'name' => $definitions->name,
            'process_version_id' => $version->id,
        ]);
        Log::debug("run process: " . (microtime(true) - $t0));
        return $request;
    }

    private function prepareBpmn(ProcessVersion $version)
    {
        $bpmn = new DOMDocument();
        $bpmn->loadXML($version->bpmn);
        // Inject SCRIPT code
        foreach ($bpmn->getElementsByTagName('scriptTask') as $script) {
            $scriptRef = $script->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'scriptRef');
            $hasScriptCode = $script->getElementsByTagName('script')->length > 0;
            if (!$hasScriptCode && $scriptRef) {
                $code = Script::findOrFail($scriptRef)->code;
                $node = $bpmn->createElementNS(BpmnDocument::BPMN_MODEL, 'script');
                $node->nodeValue = $code;
                $script->appendChild($node);
            }
        }
        return $bpmn->saveXML();
    }

    private function getDeployName(Definitions $definitions, ProcessVersion $version)
    {
        return "process_{$definitions->id}_{$version->id}.bpmn";
    }

    public function deploy(Definitions $definitions, ProcessVersion $version)
    {
        $t0 = microtime(true);
        $name = $this->getDeployName($definitions, $version);
        Log::debug($name);
        //return $name;
        Storage::disk('local')->put($name, $this->prepareBpmn($version));
        $filepath = Storage::disk('local')->path($name);
        // POST file TO 127.0.0.1:3000/deploy
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => env('NAYRA_SERVER_URL') . '/deploy.php',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => ['file'=> new CURLFILE($filepath, '', \basename($filepath))],
        ]);
        $response = curl_exec($curl);
        Log::debug($response);
        curl_close($curl);
        //Delete file
        // Storage::disk('local')->delete($name);
        /*$curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'http://127.0.0.1:3000/deploy2',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode([
                'name' => $name,
                'bpmn' => $version->bpmn,
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
        ]);
        $response = curl_exec($curl);
        Log::debug($response);
        curl_close($curl);*/
        /*$this->postByStream('http://127.0.0.1:3000/deploy2', [
            'name' => $name,
            'bpmn' => $version->bpmn,
        ]);*/
        Log::debug("deploy: " . (microtime(true) - $t0));
        //Return deploy name
        return $name;
    }

    private function action(array $body, array $properties = [])
    {
        $t0 = microtime(true);
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => env('NAYRA_SERVER_URL') . '/actions.php',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
        ]);
        $response = curl_exec($curl);
        Log::debug($response);
        $transactions = json_decode($response, true);
        curl_close($curl);
        Log::debug("action: " . (microtime(true) - $t0));
        $t0 = microtime(true);
        $transactions = $this->mergeTransactions($transactions);
        // Log::debug($transactions);
        $instance = $this->storeEntities($transactions, $properties);
        Log::debug("store: " . (microtime(true) - $t0));
        return $instance;
    }

    private function postByStream(string $url, array $data)
    {
        $postdata = json_encode($data);
        $opts = ['http' =>
            [
                'method'  => 'POST',
                'header'  => 'Content-type: application/json',
                'content' => $postdata,
            ]
        ];

        $context = stream_context_create($opts);

        try {
            $response = file_get_contents(env('NAYRA_SERVER_URL') . '/deploy2', false, $context);
        } catch (\ErrorException $ex) {
            throw new \Exception($ex->getMessage(), $ex->getCode(), $ex->getPrevious());
        }
        if ($response === false) {
            throw new \Exception();
        }

        return $response;
    }

    private function mergeTransactions(array $transactions)
    {
        $merged = [];
        foreach ($transactions as $transaction) {
            if (!isset($merged[$transaction['id']])) {
                $merged[$transaction['id']] = $transaction;
            } else {
                $merged[$transaction['id']]['properties'] = array_merge($merged[$transaction['id']]['properties'], $transaction['properties']);
            }
        }
        return $merged;
    }

    private function storeEntities(array $transactions, array $extra_properties = [])
    {
        $entities = [
            'request' => ProcessRequest::class,
            'task' => ProcessRequestToken::class,
        ];
        $uids = [];
        $firstModel = null;
        foreach ($transactions as $transaction) {
            $entity = $entities[$transaction['entity']];
            $properties = $transaction['properties'];
            switch ($transaction['type']) {
                case 'create':
                    if (isset($properties['request_id'])) {
                        $properties['request_id'] = $uids[$properties['request_id']];
                    }
                    if (isset($extra_properties) && !$firstModel) {
                        foreach ($extra_properties as $key => $value) {
                            $properties[$key] = $value;
                        }
                    }
                    $uid = $properties['id'];
                    unset($properties['id']);
                    unset($properties['extra_properties']);
                    Log::debug($entity . ': ' . json_encode($properties));
                    switch ($entity) {
                        case ProcessRequest::class:
                            $model = $entity::create([
                                'process_id' => $extra_properties['process_id'],
                                'data' => $properties['data'],
                                'status' => $properties['status'],
                                'user_id' => $extra_properties['user_id'],
                                'callable_id' => $extra_properties['callable_id'],
                                'name'  => $extra_properties['name'],
                                'process_version_id' => $extra_properties['process_version_id'],
                            ]);
                            break;
                        case ProcessRequestToken::class:
                            $model = $entity::create([
                                'user_id' => $extra_properties['user_id'],
                                'process_id' => $extra_properties['process_id'],
                                'process_request_id' => $properties['request_id'],
                                'element_id' => $properties['element_id'],
                                'element_name' => $properties['element_name'],
                                'element_type' => $properties['element_type'],
                                'status' => $properties['status'],
                            ]);
                            break;
                    }
                    $uids[$uid] = $model->id;
                    if (!$firstModel) {
                        $firstModel = $model;
                    }
                    break;
                case 'update':
                    $id = $uids[$transaction['id']] ?? $transaction['id'];
                    switch ($entity) {
                        case ProcessRequest::class:
                            $model = $entity::find($id);
                            $model->fill($properties);
                            $model->save();
                            $model->notifyProcessUpdated('ACTIVITY_ACTIVATED');
                            if ($model->status === 'COMPLETED') {
                                event(new ProcessCompleted($model));
                            }
                            break;
                        case ProcessRequestToken::class:
                            $model = $entity::find($id);
                            $model->fill($properties);
                            $model->save();
                            break;
                    }
            }
        }
        return $firstModel;
    }

    /**
     * Start a process instance.
     *
     * @param Definitions $definitions
     * @param ProcessInterface $process
     * @param array $data
     *
     * @return \ProcessMaker\Models\ProcessRequest
     */
    public function callProcess(Definitions $definitions, ProcessInterface $process, array $data)
    {
        //Validate data
        $this->validateData($data, $definitions, $process);
        //Validate user permissions
        //Validate BPMN rules
        //Log BPMN actions
        //Schedule BPMN Action
        return CallProcess::dispatchNow($definitions, $process, $data);
    }

    /**
     * Run a script task.
     *
     * @param ScriptTaskInterface $scriptTask
     * @param Token $token
     */
    public function runScripTask(ScriptTaskInterface $scriptTask, Token $token)
    {
        Log::info('Dispatch a script task: ' . $scriptTask->getId() . ' #' . $token->getId());
        $instance = $token->processRequest;
        $process = $instance->process;
        RunScriptTask::dispatch($process, $instance, $token, [])->onQueue('bpmn');
    }

    /**
     * Run a service task.
     *
     * @param ServiceTaskInterface $serviceTask
     * @param Token $token
     */
    public function runServiceTask(ServiceTaskInterface $serviceTask, Token $token)
    {
        Log::info('Dispatch a service task: ' . $serviceTask->getId());
        $instance = $token->processRequest;
        $process = $instance->process;
        RunServiceTask::dispatch($process, $instance, $token, []);
    }

    /**
     * Catch a signal event.
     *
     * @param ServiceTaskInterface $serviceTask
     * @param Token $token
     * @deprecated 4.0.15 Use WorkflowManager::throwSignalEventDefinition()
     */
    public function catchSignalEvent(ThrowEventInterface $source = null, EventDefinitionInterface $sourceEventDefinition, TokenInterface $token)
    {
        $this->throwSignalEventDefinition($sourceEventDefinition, $token);
    }

    /**
     * Throw a signal event.
     *
     * @param EventDefinitionInterface $sourceEventDefinition
     * @param Token $token
     */
    public function throwSignalEventDefinition(EventDefinitionInterface $sourceEventDefinition, TokenInterface $token)
    {
        $signalRef = $sourceEventDefinition->getProperty('signal') ?
            $sourceEventDefinition->getProperty('signal')->getId() :
            $sourceEventDefinition->getProperty('signalRef');

        if (!$signalRef) {
            return;
        }

        $requestData = $token->getInstance()->getDataStore()->getData();
        $eventConfig = json_decode($sourceEventDefinition->getProperty('config') ?? null);
        $payload = $eventConfig && $eventConfig->payload ? $eventConfig->payload[0] : null;
        $payloadId = $payload && $payload->id ? $payload->id : null;

        $data = [];

        switch ($payloadId) {
            case 'REQUEST_VARIABLE':
                if ($payload->variable) {
                    $extractedData = Arr::get($requestData, $payload->variable);
                    Arr::set($data, $payload->variable, $extractedData);
                }
                break;
            case 'EXPRESSION':
                $expression = $payload->expression;
                $formalExp = new FormalExpression();
                $formalExp->setLanguage('FEEL');
                $formalExp->setBody($expression);
                $expressionResult = $formalExp($requestData);
                Arr::set($data, $payload->variable, $expressionResult);
                break;
            case 'NONE':
                $data = [];
                break;
            default:
                $data = $requestData;
                break;
        }

        $excludeProcesses = [$token->getInstance()->getModel()->process_id];
        $excludeRequests = [];
        $instances = $token->getInstance()->getProcess()->getEngine()->getExecutionInstances();
        foreach ($instances as $instance) {
            $excludeRequests[] = $instance->getId();
        }
        ThrowSignalEvent::dispatch($signalRef, $data, $excludeProcesses, $excludeRequests)->onQueue('bpmn');
    }

    /**
     * Throw a signal event by id (signalRef).
     *
     * @param string $signalRef
     * @param array $data
     * @param array $exclude
     */
    public function throwSignalEvent($signalRef, array $data = [], array $exclude = [])
    {
        ThrowSignalEvent::dispatch($signalRef, $data, $exclude)->onQueue('bpmn');
    }

    /**
     * Catch a signal event.
     *
     * @param EventDefinitionInterface $sourceEventDefinition
     * @param Token $token
     */
    public function throwMessageEvent($instanceId, $elementId, $messageRef, array $payload = [])
    {
        ThrowMessageEvent::dispatch($instanceId, $elementId, $messageRef, $payload)->onQueue('bpmn');
    }

    /**
     * Attach validation event
     *
     * @param callable $callback
     * @return void
     */
    public function onDataValidation($callback)
    {
        $this->validations[] = $callback;
    }

    /**
     * Validate data
     *
     * @param array $data
     * @param Definitions $Definitions
     * @param EntityInterface $element
     *
     * @return void
     */
    public function validateData(array $data, Definitions $Definitions, EntityInterface $element)
    {
        $this->validator = Validator::make($data, []);
        foreach ($this->validations as $validation) {
            call_user_func($validation, $this->validator, $Definitions, $element);
        }
        $this->validator->validate($data);
    }

    /**
     * Run a process and returns its data
     *
     * @param Definitions $definitions
     * @param string $startId
     * @param array $data
     *
     * @return array
     */
    public function runProcess(Definitions $definitions, $startId, array $data)
    {
        $startEvent = $definitions->getDefinitions()->getStartEvent($startId);
        $instance = $this->triggerStartEvent($definitions, $startEvent, $data);

        return $instance->getDataStore()->getData();
    }

    /**
     * Check if service task implementation exists
     *
     * @param string $implementation
     *
     * @return bool
     */
    public function registerServiceImplementation($implementation, $class)
    {
        if (!class_exists($class)) {
            return false;
        }

        // check class instance of ServiceTaskImplementationInterface
        if (!is_subclass_of($class, ServiceTaskImplementationInterface::class)) {
            return false;
        }

        $this->serviceTaskImplementations[$implementation] = $class;

        return true;
    }

    /**
     * Check if service task implementation exists
     *
     * @param string $implementation
     *
     * @return bool
     */
    public function existsServiceImplementation($implementation)
    {
        return isset($this->serviceTaskImplementations[$implementation]) &&
            class_exists($this->serviceTaskImplementations[$implementation]);
    }

    /**
     * Run the service task implementation
     * @param string $implementation
     * @param array $dat
     * @param array $config
     * @param string $tokenId
     *
     * @return mixed
     */
    public function runServiceImplementation($implementation, array $data, array $config, $tokenId = '')
    {
        $class = $this->serviceTaskImplementations[$implementation];
        $service = new $class();

        return $service->run($data, $config, $tokenId);
    }
}
