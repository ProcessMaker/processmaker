<?php

namespace ProcessMaker\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Exception\ScriptException;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\Process as Definitions;
use ProcessMaker\Models\Script;
use ProcessMaker\Nayra\Contracts\Bpmn\ScriptTaskInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;
use Throwable;

class RunScriptTask extends BpmnAction implements ShouldQueue
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
    public function action(TokenInterface $token, ScriptTaskInterface $element, Definitions $processModel)
    {
        $scriptRef = $element->getProperty('scriptRef');
        Log::info('Script started: ' . $scriptRef);
        $configuration = json_decode($element->getProperty('config'), true);

        // Check to see if we've failed parsing.  If so, let's convert to empty array.
        if ($configuration === null) {
            $configuration = [];
        }
        $dataStore = $token->getInstance()->getDataStore();
        $data = $dataStore->getData();
        try {
            if (empty($scriptRef)) {
                $code = $element->getScript();
                if (empty($code)) {
                    throw new ScriptException(__('No code or script assigned to ":name"', ['name' => $element->getName()]));
                }
                $script = new Script([
                    'code' => $code,
                    'language' => Script::scriptFormat2Language($element->getProperty('scriptFormat', 'application/x-php')),
                    'run_as_user_id' => Script::defaultRunAsUser()->id,
                ]);
            } else {
                $script = Script::find($scriptRef);
            }

            $response = $script->runScript($data, $configuration);
            // Update data
            if (is_array($response['output'])) {
                // Validate data
                WorkflowManager::validateData($response['output'], $processModel, $element);
                foreach ($response['output'] as $key => $value) {
                    $dataStore->putData($key, $value);
                }
            }
            $element->complete($token);
            Log::info('Script completed: ' . $scriptRef);
        } catch (Throwable $exception) {
            // Change to error status
            $token->setStatus(ScriptTaskInterface::TOKEN_STATE_FAILING);
            $token->getInstance()->logError($exception, $element);
            Log::info('Script failed: ' . $scriptRef . ' - ' . $exception->getMessage());
        }
    }
}
