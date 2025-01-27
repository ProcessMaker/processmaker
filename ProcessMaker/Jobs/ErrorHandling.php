<?php

namespace ProcessMaker\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use ProcessMaker\Exception\ScriptException;
use ProcessMaker\Exception\ScriptTimeoutException;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptVersion;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ScriptTaskInterface;
use ProcessMaker\Notifications\ErrorExecutionNotification;
use Throwable;

class ErrorHandling
{
    /**
     * Error handling settings that are set in modeler.
     *
     * These settings take precedence over those set in script or
     * data source configuration.
     *
     * @var array
     */
    public $bpmnErrorHandling = [];

    /**
     * Default settings that are set in script or data source configuration.
     *
     * $bpmnErrorHandling takes precedence over these if set.
     *
     * @var array
     */
    public $defaultErrorHandling = [];

    public $job;

    public $exception;

    public $metadata;

    public function __construct(
        public ActivityInterface $element,
        public ProcessRequestToken $processRequestToken,
    ) {
        $this->bpmnErrorHandling = json_decode($element->getProperty('errorHandling'), true) ?? [];
    }

    public function handleRetries(ShouldQueue $job, Throwable $exception)
    {
        $this->job = $job;
        $this->exception = $exception;

        return $this->handldRetryAttempts();
    }

    public function handleRetriesForScriptMicroservice(Throwable $exception, array $metadata)
    {
        $this->metadata = $metadata;
        $this->exception = $exception;

        return $this->handldRetryAttempts();
    }

    private function handldRetryAttempts()
    {
        $finalAttempt = true;
        $attemptNumber = $this->getAttemptNumber();
        $message = $this->getMessage();

        if ($this->retryAttempts() > 0) {
            if ($attemptNumber <= $this->retryAttempts()) {
                Log::info('Retry the job process. Attempt ' . $attemptNumber . ' of ' . $this->retryAttempts() . ', Wait time: ' . $this->retryWaitTime());
                $this->requeue();

                $finalAttempt = false;

                return [$message, $finalAttempt];
            }

            $message = __('Job failed after :attempts total attempts', ['attempts' => $attemptNumber]) . "\n" . $message;

            $this->sendExecutionErrorNotification($message);
        } else {
            $this->sendExecutionErrorNotification($message);
        }

        return [$message, $finalAttempt];
    }

    private function requeue()
    {
        if ($this->job instanceof RunNayraServiceTask) {
            $newJob = new RunNayraServiceTask($this->processRequestToken);
            $newJob->attemptNum = $this->attemptNumber + 1;
        } else {
            $jobClass = $this->getJobClass();
            $newJob = new $jobClass(
                $this->processRequestToken->process,
                $this->processRequestToken->processRequest,
                $this->processRequestToken,
                $this->getData(),
                $this->getAttemptNumber() + 1
            );
        }
        $newJob->delay($this->retryWaitTime());
        $newJob->onQueue('bpmn');
        dispatch($newJob);
    }

    private function getData()
    {
        return $this->job ? $this->job->data : $this->metadata['script_task']['data'];
    }

    private function getAttemptNumber()
    {
        return $this->job ? $this->job->attemptNum : $this->metadata['script_task']['attempt_num'];
    }

    private function getMessage()
    {
        return $this->exception->getMessage();
    }

    private function getJobClass()
    {
        return $this->job ? get_class($this->job) : $this->metadata['script_task']['job_class'];
    }

    /**
     * Send execution error notification.
     */
    public function sendExecutionErrorNotification(string $message)
    {
        if ($this->processRequestToken) {
            $user = $this->processRequestToken->processRequest->processVersion->manager;
            if ($user !== null) {
                Log::info('Send Execution Error Notification: ' . $message);
                Notification::send($user, new ErrorExecutionNotification($this->processRequestToken, $message, $this->bpmnErrorHandling));
            }
        }
    }

    /**
     * Get the effective timeout
     *
     * @return int
     */
    public function timeout()
    {
        return $this->get('timeout');
    }

    /**
     * Get the effective retry attempts
     *
     * @return int
     */
    public function retryAttempts()
    {
        return $this->get('retry_attempts');
    }

    /**
     * Get the effective retry wait time.
     *
     * @return int
     */
    public function retryWaitTime()
    {
        return $this->get('retry_wait_time');
    }

    /**
     * Get the attribute from the bpmnErrorHandling array but if it's not set
     * return the defaultErrorHandling value
     *
     * @param string $attribute
     * @return void
     */
    public function get($attribute)
    {
        $value = Arr::get(
            $this->bpmnErrorHandling,
            $attribute,
            null
        );

        if ($value === null || $value === '') {
            $value = Arr::get($this->defaultErrorHandling, $attribute, 0);
        }

        return (int) $value;
    }

    /**
     * Set defaults from script model
     *
     * @param ScriptVersion $script
     * @return void
     */
    public function setDefaultsFromScript(Script|ScriptVersion $script)
    {
        $this->defaultErrorHandling = [
            'timeout' => $script->timeout,
            'retry_attempts' => $script->retry_attempts,
            'retry_wait_time' => $script->retry_wait_time,
        ];
    }

    /**
     * Set defaults from data source model endpoint
     *
     * @param array $config
     * @return void
     */
    public function setDefaultsFromDataSourceConfig(array $config)
    {
        // If this is not a Data Connecter, don't do any error handling
        $id = Arr::get($config, 'dataSource', null);
        if (!$id) {
            return;
        }

        // Check if the data source package is installed
        $class = "ProcessMaker\Packages\Connectors\DataSources\Models\DataSource";
        if (!class_exists($class)) {
            return;
        }

        // Check if the data source exists
        $dataSource = $class::find($id);
        if (!$dataSource) {
            return;
        }

        // Check if the endpoint config exists in the data source
        $endpointConfig = Arr::get($dataSource->endpoints, Arr::get($config, 'endpoint', null));
        if (!$endpointConfig) {
            return;
        }

        $this->defaultErrorHandling = [
            'timeout' => Arr::get($endpointConfig, 'timeout', 0),
            'retry_attempts' => Arr::get($endpointConfig, 'retry_attempts', 0),
            'retry_wait_time' => Arr::get($endpointConfig, 'retry_wait_time', 5),
        ];
    }

    public static function convertErrorResponseToException($response)
    {
        if ($response['status'] === 'error') {
            if (str_starts_with($response['message'], 'Command exceeded timeout of')) {
                throw new ScriptTimeoutException($response['message']);
            }
            throw new ScriptException($response['message']);
        }
    }
}
