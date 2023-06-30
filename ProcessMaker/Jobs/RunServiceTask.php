<?php

namespace ProcessMaker\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
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
        $errorHandling = json_decode($element->getProperty('errorHandling'), true) ?? [];

        // Check to see if we've failed parsing.  If so, let's convert to empty array.
        if ($configuration === null) {
            $configuration = [];
        }
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

            //todo: It is necessary to obtain the configuration values of the "dataSource" for the default values.
            $this->errorHandling($errorHandling);

            $this->unlock();
            $dataManager = new DataManager();
            $data = $dataManager->getData($token);

            if ($existsImpl) {
                $response = [
                    'output' => WorkflowManager::runServiceImplementation($implementation, $data, $configuration, $token->getId()),
                ];
            } else {
                $response = $script->runScript($data, $configuration, $token->getId(), $errorHandling);
            }
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
        } catch (Throwable $exception) {
            // Change to error status
            $token->setStatus(ServiceTaskInterface::TOKEN_STATE_FAILING);
            $error = $element->getRepository()->createError();
            $error->setName($exception->getMessage());
            $token->setProperty('error', $error);
            Log::info('Service task failed: ' . $implementation . ' - ' . $exception->getMessage());
            Log::error($exception->getTraceAsString());
            
            if ($this->retryAttempts() > 0) {
                if ($this->attempts() <= $this->retryAttempts()) {
                    Log::info('Retry the runScript process. Attempt ' . $this->attempts() . ' of ' . $this->retryAttempts() . ', Wait time: ' . $this->retryWaitTime());
                    $this->release($this->retryWaitTime());
                    return;
                }
                $message = __('Failed after :num total attempts', ['num' => $this->attempts()]);
                $message = $message . "\n" . $exception->getMessage();
                
                $this->sendExecutionErrorNotification($message, $token->getId(), $errorHandling);
                throw $exception;
            } else {
                $this->sendExecutionErrorNotification($exception->getMessage(), $token->getId(), $errorHandling);
                throw $exception;
            }
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
        if (get_class($exception) === "Illuminate\\Queue\\MaxAttemptsExceededException") {
            $message = 'This is a type MaxAttemptsExceededException exception, it appears '
                . 'that the global value configured in config/horizon.php has been exceeded '
                . 'in the retries. Please consult with your main administrator.';
            Log::error($message);
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
