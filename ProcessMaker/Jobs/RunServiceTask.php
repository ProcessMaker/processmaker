<?php

namespace ProcessMaker\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Exception\ScriptException;
use ProcessMaker\Models\Process as Definitions;
use ProcessMaker\Models\Script;
use ProcessMaker\Nayra\Contracts\Bpmn\ServiceTaskInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;

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
     * @param \ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface $instance
     * @param \ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface $token
     * @param array $data
     */
    public function __construct(Definitions $definitions, ExecutionInstanceInterface $instance, TokenInterface $token, array $data)
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
    public function action(TokenInterface $token, ServiceTaskInterface $element)
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
        $response = $script->runScript($data, $configuration);
        if (is_array($response['output'])) {
            foreach ($response['output'] as $key => $value) {
                $dataStore->putData($key, $value);
            }
            $element->complete($token);
            Log::info('Service task completed: ' . $implementation);
        } else {
            $token->setStatus(ServiceTaskInterface::TOKEN_STATE_FAILING);
            Log::info('Service task failed: ' . $implementation . ' - ' . $response['output']);
        }
    }
}
