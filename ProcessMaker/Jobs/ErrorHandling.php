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

            // Keep executor diagnostics before reducing the response to a UI exception.
            // Do not log the response wholesale: metadata/output can contain request data or secrets.
            Log::error('Script microservice execution failed', [
                'message' => $message,
                'diagnostics' => self::scriptErrorDiagnostics(Arr::only($result, [
                    'error_message', 'error', 'exception', 'message',
                ])),
                'output_diagnostics' => self::scriptErrorDiagnostics(
                    is_array($result['output'] ?? null)
                        ? Arr::only($result['output'], ['error_message', 'error', 'exception', 'code', 'file', 'line', 'trace'])
                        : []
                ),
                'correlation' => self::scriptErrorDiagnostics(Arr::only(
                    is_array($result['metadata'] ?? null) ? $result['metadata'] : [],
                    ['script_id', 'executor_uuid', 'executor_type', 'executor_version', 'instance_uuid', 'script_task']
                )),
            ]);

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

        $fallback = '';
        foreach ($candidates as $candidate) {
            $message = self::extractMessageCandidate($candidate);
            if (!empty($message)) {
                // Microservice 1.2.1 returns the exit status in error_message and the
                // actionable cause in error.error (1.0.0 used message/output.error).
                // Contract fixtures cover both formats; do not gate this on an executor image tag.
                if (!preg_match('/^Script execution failed(?: with exit code:\s*-?\d+| with unknown error)?[.!]?$/i', $message)) {
                    return $message;
                }
                $fallback = $fallback ?: $message;
            }
        }

        return $fallback;
    }

    /**
     * Retain bounded, sanitized executor diagnostics and identifiers, never stack arguments.
     */
    private static function scriptErrorDiagnostics(mixed $value, int $depth = 0): mixed
    {
        if (is_string($value)) {
            return mb_strcut(self::redactScriptErrorDetails($value), 0, 8000, 'UTF-8');
        }
        if (is_int($value) || is_float($value) || is_bool($value) || $value === null) {
            return $value;
        }
        if (!is_array($value) || $depth >= 4) {
            return null;
        }

        $allowed = ['error_message', 'message', 'detail', 'error', 'exception', 'code', 'file', 'line',
            'trace', 'class', 'function', 'type', 'script_id', 'executor_uuid', 'executor_type',
            'executor_version', 'instance_uuid', 'script_task', 'definition_id', 'instance_id', 'token_id'];
        $diagnostics = [];
        foreach (array_slice($value, 0, 30, true) as $key => $item) {
            if ($key === 'trace') {
                // Text traces may contain unlabeled arguments. Keep only structured frames.
                $frames = self::scriptTraceDiagnostics($item, $depth + 1);
                if ($frames !== []) {
                    $diagnostics[$key] = $frames;
                }
                continue;
            }
            if (is_int($key) || in_array($key, $allowed, true)) {
                $diagnostics[$key] = self::scriptErrorDiagnostics($item, $depth + 1);
            }
        }

        return $diagnostics;
    }

    private static function scriptTraceDiagnostics(mixed $trace, int $depth): array
    {
        if (!is_array($trace) || $depth >= 4) {
            return [];
        }

        $frames = [];
        foreach (array_slice(array_is_list($trace) ? $trace : [$trace], 0, 30) as $frame) {
            if (!is_array($frame)) {
                continue;
            }
            $fields = array_filter(
                Arr::only($frame, ['file', 'line', 'class', 'function', 'type']),
                fn ($field) => is_string($field) || is_int($field)
            );
            if ($fields !== []) {
                $frames[] = self::scriptErrorDiagnostics($fields, $depth + 1);
            }
        }

        return $frames;
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
            '/("(?:authorization|api[_-]?(?:token|key)|(?:access|refresh|callback)[_-]?token|client[_-]?secret|password)"\s*:\s*)"(?:\\\\.|[^"\\\\\r\n])*"/i',
            '$1"[REDACTED]"',
            $details
        ) ?? $details;
        $redacted = preg_replace(
            '/\bauthorization\b\s*[:=]\s*[^\r\n]*/i',
            'Authorization=[REDACTED]',
            $redacted
        ) ?? $redacted;
        $redacted = preg_replace('/\bBearer\s+\S+/i', 'Bearer [REDACTED]', $redacted) ?? $redacted;
        $redacted = preg_replace(
            '/\b(api[_-]?(?:token|key)|(?:access|refresh|callback)[_-]?token|client[_-]?secret|password)\b\s*[:=]\s*[^\s,;]+/i',
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
