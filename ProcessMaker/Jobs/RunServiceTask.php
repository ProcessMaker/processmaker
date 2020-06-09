<?php

namespace ProcessMaker\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Exception\ScriptException;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\Process as Definitions;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Script;
use ProcessMaker\Nayra\Contracts\Bpmn\ServiceTaskInterface;
use Throwable;

class RunServiceTask extends BpmnAction implements ShouldQueue
{
    public $definitionsId;
    public $instanceId;
    public $tokenId;
    public $data;

    /**
     * Create a new job instance.
     *
     * @param \ProcessMaker\Models\Process $definitions
     * @param \ProcessMaker\Models\ProcessRequest $instance
     * @param \ProcessMaker\Models\ProcessRequestToken $token
     * @param array $data
     */
    public function __construct(Definitions $definitions, ProcessRequest $instance, ProcessRequestToken $token, array $data)
    {
        $this->definitionsId = $definitions->getKey();
        $this->instanceId = $instance->getKey();
        $this->tokenId = $token->getKey();
        $this->data = $data;
    }

    /**
     * Execute the script task.
     *
     * @return void
     */
    public function action(ProcessRequestToken $token, ServiceTaskInterface $element, Definitions $processModel, ProcessRequest $instance)
    {
        $implementation = $element->getImplementation();
        Log::info('Service task started: ' . $implementation);
        $configuration = json_decode($element->getProperty('config'), true);

        // Check to see if we've failed parsing.  If so, let's convert to empty array.
        if ($configuration === null) {
            $configuration = [];
        }
        $dataStore = $token->getInstance()->getDataStore();
        $data = $dataStore->getData();
        try {
            if (empty($implementation)) {
                Log::error('Service task implementation not defined');
                throw new ScriptException('Service task implementation not defined');
            } else {
                $script = Script::where('key', $implementation)->first();
            }
            if (empty($script)) {
                Log::error('Service task not implemented: ' . $implementation);
                throw new ScriptException('Service task not implemented: ' . $implementation);
            }

            $this->unlockInstance($instance->getKey());
            $response = $script->runScript($data, $configuration);

            $this->withUpdatedContext(function ($engine, $instance, $element, $processModel, $token) use ($response) {
                $dataStore = $token->getInstance()->getDataStore();
                // Update data
                if (is_array($response['output'])) {
                    // Validate data
                    WorkflowManager::validateData($response['output'], $processModel, $element);
                    foreach ($response['output'] as $key => $value) {
                        $dataStore->putData($key, $value);
                    }
                }
                $element->complete($token);
                $this->engine = $engine;
                $this->instance = $instance;
            });
            Log::info('Service task completed: ' . $implementation);
        } catch (Throwable $exception) {
            // Change to error status
            $token->setStatus(ServiceTaskInterface::TOKEN_STATE_FAILING);
            $token->getInstance()->logError($exception, $element);
            Log::info('Service task failed: ' . $implementation . ' - ' . $exception->getMessage());
        }
    }
}
