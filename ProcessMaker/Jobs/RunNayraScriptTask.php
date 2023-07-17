<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Exception\ConfigurationException;
use ProcessMaker\Exception\ScriptException;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Managers\DataManager;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\Nayra\Contracts\Bpmn\ScriptTaskInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use Throwable;

/**
 * This job runs a script task with custom language like nodejs
 */
class RunNayraScriptTask implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    public $tokenId;

    public $userId;

    /**
     * Create a new job instance.
     *
     * @param \ProcessMaker\Models\ProcessRequestToken $token
     * @param array $data
     */
    public function __construct(TokenInterface $token)
    {
        $this->tokenId = $token->getKey();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Get token
        $token = ProcessRequestToken::find($this->tokenId);
        $token->loadTokenProperties();
        $instance = $token->processRequest;
        $processModel = $token->process;
        $instance->loadProcessRequestInstance();
        $token->setInstance($instance);
        $element = $token->getDefinition(true);

        // Exit if the task was completed or closed
        if (!$token || !$element) {
            return;
        }
        $scriptRef = $element->getProperty('scriptRef');
        $configuration = json_decode($element->getProperty('config'), true);
        $errorHandling = json_decode($element->getProperty('errorHandling'), true);
        if ($errorHandling === null) {
            $errorHandling = [];
        }

        // Check to see if we've failed parsing.  If so, let's convert to empty array.
        if ($configuration === null) {
            $configuration = [];
        }
        try {
            if (empty($scriptRef)) {
                $code = $element->getScript();
                if (empty($code)) {
                    throw new ConfigurationException(__('No code or script assigned to ":name"', ['name' => $element->getName()]));
                }
                $language = Script::scriptFormat2Language($element->getProperty('scriptFormat', 'application/x-php'));
                $script = new Script([
                    'code' => $code,
                    'language' => $language,
                    'run_as_user_id' => Script::defaultRunAsUser()->id,
                    'script_executor_id' => ScriptExecutor::initialExecutor($language)->id,
                ]);
            } else {
                $script = Script::find($scriptRef);
                if (!$script) {
                    throw new ConfigurationException(__('Script ":id" not found', ['id' => $scriptRef]));
                }
                $script = $script->versionFor($instance);
            }

            $dataManager = new DataManager();
            $data = $dataManager->getData($token);
            $response = $script->runScript($data, $configuration, $token->getId(), $errorHandling);

            // Dispatch complete task action
            WorkflowManager::completeTask($processModel, $instance, $token, $response['output']);
        } catch (ConfigurationException $exception) {
            $output = ['ScriptConfigurationError' => $element->getName() . ': ' . $exception->getMessage()];
            WorkflowManager::completeTask($processModel, $instance, $token, $output);
        } catch (Throwable $exception) {
            Log::error('Script failed: ' . $scriptRef . ' - ' . $exception->getMessage());
            Log::error($exception->getTraceAsString());
            WorkflowManager::taskFailed($instance, $token, $exception->getMessage());
        }
    }

    /**
     * When Job fails
     */
    public function failed(Throwable $exception)
    {
        if (!$this->tokenId) {
            Log::error('Script failed: ' . $exception->getMessage());

            return;
        }
        Log::error('Script (#' . $this->tokenId . ') failed: ' . $exception->getMessage());
        $token = ProcessRequestToken::find($this->tokenId);
        if ($token) {
            $element = $token->getBpmnDefinition();
            $token->setStatus(ScriptTaskInterface::TOKEN_STATE_FAILING);
            $error = $element->getRepository()->createError();
            $error->setName($exception->getMessage());
            $token->setProperty('error', $error);
            Log::error($exception->getTraceAsString());
            $token->save();
        }
    }
}
