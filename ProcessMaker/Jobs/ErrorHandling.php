<?php

namespace ProcessMaker\Jobs;

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
use ProcessMaker\Notifications\ErrorExecutionNotification;

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

    public function __construct(
        public $element,
        public $processRequestToken,
    ) {
        $this->bpmnErrorHandling = json_decode($element->getProperty('errorHandling'), true) ?? [];
    }

    public function handleRetries($job, $exception)
    {
        $message = $exception->getMessage();
        $finalAttempt = true;

        if ($this->retryAttempts() > 0) {
            if ($job->attemptNum <= $this->retryAttempts()) {
                Log::info('Retry the job process. Attempt ' . $job->attemptNum . ' of ' . $this->retryAttempts() . ', Wait time: ' . $this->retryWaitTime());
                $this->requeue($job);

                $finalAttempt = false;

                return [$message, $finalAttempt];
            }

            $message = __('Job failed after :attempts total attempts', ['attempts' => $job->attemptNum]) . "\n" . $message;

            $this->sendExecutionErrorNotification($message);
        } else {
            $this->sendExecutionErrorNotification($message);
        }

        return [$message, $finalAttempt];
    }

    private function requeue($job)
    {
        $class = get_class($job);
        if ($job instanceof RunNayraServiceTask) {
            $newJob = new RunNayraServiceTask($this->processRequestToken);
            $newJob->attemptNum = $job->attemptNum + 1;
        } else {
            $newJob = new $class(
                Process::findOrFail($job->definitionsId),
                ProcessRequest::findOrFail($job->instanceId),
                ProcessRequestToken::findOrFail($job->tokenId),
                $job->data,
                $job->attemptNum + 1
            );
        }
        $newJob->delay($this->retryWaitTime());
        $newJob->onQueue('bpmn');
        dispatch($newJob);
    }

    /**
     * Send execution error notification.
     */
    public function sendExecutionErrorNotification(string $message)
    {
        if ($this->processRequestToken) {
            // review no multiple managers
            $mangers = $this->processRequestToken->processRequest->processVersion->getManagers();
            foreach ($mangers as $manager) {
                Log::info('Send Execution Error Notification: ' . $message . ' to manager: ' . $manager->username);
                Notification::send($manager, new ErrorExecutionNotification($this->processRequestToken, $message, $this->bpmnErrorHandling));
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

    public static function convertResponseToException($result)
    {
        if ($result['status'] === 'error') {
            $rawMessage = $result['message'] ?? '';
            if (str_starts_with((string) $rawMessage, 'Command exceeded timeout of')) {
                throw new ScriptTimeoutException((string) $rawMessage);
            }

            $message = self::extractScriptErrorMessage($result);

            if (empty($message)) {
                $message = $rawMessage ?: 'Script execution failed with unknown error';
            }

            throw new ScriptException($message);
        }
    }

    /**
     * Extract a concise error message from the microservice response.
     */
    private static function extractScriptErrorMessage(array $result): string
    {
        $candidates = [
            $result['error_message'] ?? null,
            $result['output']['error_message'] ?? null,
            $result['error'] ?? null,
            $result['output']['error'] ?? null,
            $result['exception'] ?? null,
            $result['output']['exception'] ?? null,
            $result['output']['stderr'] ?? null,
            $result['output']['stdout'] ?? null,
            $result['message'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            $message = self::extractMessageCandidate($candidate);
            if (!empty($message)) {
                return $message;
            }
        }

        return '';
    }

    /**
     * Extract a message only from known human-readable fields.
     */
    private static function extractMessageCandidate(mixed $candidate, int $depth = 0): string
    {
        if (is_string($candidate) || is_numeric($candidate)) {
            return self::sanitizeScriptErrorMessage((string) $candidate);
        }

        if (!is_array($candidate) || $depth >= 3) {
            return '';
        }

        foreach (['error_message', 'message', 'detail', 'error', 'exception'] as $key) {
            if (!array_key_exists($key, $candidate)) {
                continue;
            }

            $message = self::extractMessageCandidate($candidate[$key], $depth + 1);
            if (!empty($message)) {
                return $message;
            }
        }

        return '';
    }

    /**
     * Keep only the first line of the error and limit its length to avoid noisy traces.
     */
    public static function sanitizeScriptErrorMessage(string $message): string
    {
        $firstLine = strtok($message, "\n");
        $firstLine = $firstLine === false ? $message : $firstLine;
        $trimmed = trim($firstLine);

        $trimmed = preg_replace(
            '/^(?:PHP\s+)?(?:Fatal error:\s*)?(?:Uncaught\s+)?(?:[\\w\\\\]*(?:Exception|Error)):\s*/i',
            '',
            $trimmed
        ) ?? $trimmed;
        $trimmed = preg_replace(
            '/\s+in\s+(?:\/|[A-Za-z]:\\\\).*(?:\s+on\s+line\s+\d+|:\d+)\s*$/i',
            '',
            $trimmed
        ) ?? $trimmed;
        $trimmed = self::redactScriptErrorDetails($trimmed);

        if (strlen($trimmed) > 400) {
            return mb_strcut($trimmed, 0, 400, 'UTF-8') . '…';
        }

        return $trimmed;
    }

    /**
     * Redact credentials while preserving multiline diagnostics for application logs.
     */
    public static function redactScriptErrorDetails(string $details): string
    {
        $redacted = preg_replace(
            '/\bauthorization\b\s*[:=]\s*[^\r\n]*/i',
            'Authorization=[REDACTED]',
            $details
        ) ?? $details;
        $redacted = preg_replace('/\bBearer\s+\S+/i', 'Bearer [REDACTED]', $redacted) ?? $redacted;
        $redacted = preg_replace(
            '/\b(api[_-]?token|access[_-]?token|client[_-]?secret|password)\b\s*[:=]\s*[^\s,;]+/i',
            '$1=[REDACTED]',
            $redacted
        ) ?? $redacted;

        return preg_replace(
            '/\beyJ[A-Za-z0-9_-]{10,}\.[A-Za-z0-9_-]{10,}\.[A-Za-z0-9_-]{10,}\b/',
            '[REDACTED]',
            $redacted
        ) ?? $redacted;
    }
}
