<?php

namespace ProcessMaker\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Exception\ConfigurationException;
use ProcessMaker\Exception\ScriptException;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Managers\DataManager;
use ProcessMaker\Models\Process as Definitions;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Script;
use ProcessMaker\Nayra\Contracts\Bpmn\ServiceTaskInterface;
use ProcessMaker\Repositories\DefinitionsRepository;
use Throwable;

class RunServiceTask extends BpmnAction implements ShouldQueue
{
    public $definitionsId;

    public $instanceId;

    public $tokenId;

    public $data;

    public $tries = 3;

    public $backoff = 60;

    public $attemptNum;

    /**
     * Create a new job instance.
     *
     * @param \ProcessMaker\Models\Process $definitions
     * @param \ProcessMaker\Models\ProcessRequest $instance
     * @param \ProcessMaker\Models\ProcessRequestToken $token
     * @param array $data
     */
    public function __construct(Definitions $definitions, ProcessRequest $instance, ProcessRequestToken $token, array $data, $attemptNum = 1)
    {
        if ($token->getOwner()) {
            $pmConfig = json_decode($token->getOwnerElement()->getProperty('config', '{}'), true);
        } else {
            $pmConfig = [];
        }
        $this->onQueue($pmConfig['queue'] ?? 'bpmn');
        $this->definitionsId = $definitions->getKey();
        $this->instanceId = $instance->getKey();
        $this->tokenId = $token->getKey();
        $this->elementId = $token->getProperty('element_ref');
        $this->data = $data;
        $this->attemptNum = $attemptNum;
    }

    /**
     * Execute the script task.
     *
     * @return void
     */
    public function action(ProcessRequestToken $token = null, ServiceTaskInterface $element = null, Definitions $processModel, ProcessRequest $instance)
    {
        // Exit if the task was completed or closed
        if (!$token || !$element) {
            return;
        }
        $implementation = $element->getImplementation();
        $configuration = json_decode($element->getProperty('config'), true);

        // Check to see if we've failed parsing.  If so, let's convert to empty array.
        if ($configuration === null) {
            $configuration = [];
        }
        $errorHandling = null;
        try {
            if (empty($implementation)) {
                throw new ScriptException('Service task implementation not defined');
            } else {
                $script = Script::where('key', $implementation)->first();
            }
            // Check if service task implementation exists
            $existsImpl = WorkflowManager::existsServiceImplementation($implementation);

            if (!$existsImpl && empty($script)) {
                throw new ScriptException('Service task not implemented: ' . $implementation);
            }

            $errorHandling = new ErrorHandling($element, $token);
            $errorHandling->setDefaultsFromDataSourceConfig($configuration);

            $this->unlock();
            $dataManager = new DataManager();
            $data = $dataManager->getData($token);

            /**
             * todo: Please review the "else" section as it appears unreachable since if `$existsImpl`
             * is not true, an exception is thrown a few lines above.
             */
            if ($existsImpl) {
                $response = [
                    'output' => WorkflowManager::runServiceImplementation($implementation, $data, $configuration, $token->getId(), $errorHandling->timeout()),
                ];
            } else {
                $response = $script->runScript($data, $configuration, $token->getId(), $errorHandling->timeout());
            }
            $this->updateData($response);
        } catch (ConfigurationException $exception) {
            $this->addDebugLog(get_class($this) . ": FAILED token={$token?->id} request={$instance->id} " . $exception->getMessage());
            $this->unlock();
            $this->updateData(['output' => $exception->getMessageForData($token)]);
        } catch (Throwable $exception) {
            $this->addDebugLog(get_class($this) . ": FAILED token={$token?->id} request={$instance->id} " . $exception->getMessage());
            $finalAttempt = true;
            if ($errorHandling) {
                [$message, $finalAttempt] = $errorHandling->handleRetries($this, $exception);
            } else {
                $message = $exception->getMessage();
            }

            if ($finalAttempt) {
                // Change to error status
                $token->setStatus(ServiceTaskInterface::TOKEN_STATE_FAILING);
            }

            $error = $element->getRepository()->createError();
            $error->setName($message);

            $token->setProperty('error', $error);
            $exceptionClass = get_class($exception);
            $modifiedException = new $exceptionClass($message);
            $token->logError($modifiedException, $element);

            Log::error('Service task failed: ' . $implementation . ' - ' . $message);
            Log::error($exception->getTraceAsString());
        }
    }

    private function updateData($response)
    {
        $this->withUpdatedContext(function ($engine, $instance, $element, $processModel, $token) use ($response) {
            // Exit if the task was completed or closed
            if (!$token || !$element) {
                return;
            }
            // Update data
            if (is_array($response['output'])) {
                // Validate data
                WorkflowManager::validateData($response['output'], $processModel, $element);
                $dataManager = new DataManager();
                $dataManager->updateData($token, $response['output']);
                $engine->runToNextState();
            }
            $element->complete($token);
            $this->engine = $engine;
            $this->instance = $instance;
        });
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
        Log::error($exception->getTraceAsString());
        $token = ProcessRequestToken::find($this->tokenId);
        if ($token) {
            $token->setStatus(ServiceTaskInterface::TOKEN_STATE_FAILING);
            $repository = new DefinitionsRepository();
            $error = $repository->createError();
            $error->setName($exception->getMessage());
            $token->setProperty('error', $error);
            $token->save();
        }
    }
}
