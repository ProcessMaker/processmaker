<?php

namespace ProcessMaker\Jobs;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptVersion;
use ProcessMaker\Notifications\ErrorExecutionNotification;

class ErrorHandling
{
    public $errorHandling = [];

    public function __construct(
        public $element,
        public $model,
        public $processRequestToken,
    ) {
        $errorHandling = json_decode($element->getProperty('errorHandling'), true) ?? [];
        \Log::info('ERROR HANDLING', ['eh' => $errorHandling]);
        \Log::info('model: ' . get_class($this->model));

        if ($this->model instanceof ScriptVersion) {
            $script = $this->model;

            if (!is_array($errorHandling) || empty($errorHandling)) {
                $errorHandling = [
                    'timeout' => $script->timeout,
                    'retry_attempts' => $script->retry_attempts,
                    'retry_wait_time' => $script->retry_wait_time,
                ];
            }
            $this->errorHandling($errorHandling);
        }

        // TODO data sources
    }

    public function handleRetries($job, $exception)
    {
        $message = $exception->getMessage();

        if ($this->retryAttempts() > 0) {
            if ($job->attempts() <= $this->retryAttempts()) {
                Log::info('Retry the runScript process. Attempt ' . $job->attempts() . ' of ' . $this->retryAttempts() . ', Wait time: ' . $this->retryWaitTime());
                $job->release($this->retryWaitTime());

                return $message;
            }

            $message = __('Script failed after :attempts total attempts', ['attempts' => $job->attempts() - 1]) . "\n" . $message;

            $this->sendExecutionErrorNotification($message);

            return $message;
        } else {
            $this->sendExecutionErrorNotification($message);
        }

        return $message;
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
                Notification::send($user, new ErrorExecutionNotification($this->processRequestToken, $message, $this->bpmnSetting));
            }
        }
    }

    /**
     * This sets the property errorHandling, and if the parameter doesn't exist,
     * it returns the current value of property errorHandling.
     *
     * @param mixed $value
     * @return mixed
     */
    public function errorHandling($value = null)
    {
        \Log::info('errorHandling', ['value' => $value]);
        if (is_array($value) && !empty($value)) {
            $array = [
                'timeout',
                'retry_attempts',
                'retry_wait_time',
            ];
            foreach ($array as $val) {
                $digit = 0;
                if (is_string($value[$val]) && ctype_digit($value[$val])) {
                    $digit = intval($value[$val]);
                }
                if (is_int($value[$val])) {
                    $digit = $value[$val];
                }
                $value[$val] = $digit;
            }
            $this->errorHandling = $value;
        }

        return $this->errorHandling;
    }

    /**
     * This retrieves the value of errorHandling['timeout'].
     *
     * @return int
     */
    public function timeout()
    {
        return $this->errorHandling['timeout'];
    }

    /**
     * This retrieves the value of errorHandling['retry_attempts'].
     *
     * @return int
     */
    public function retryAttempts()
    {
        return $this->errorHandling['retry_attempts'];
    }

    /**
     * This retrieves the value of errorHandling['retry_wait_time'].
     *
     * @return int
     */
    public function retryWaitTime()
    {
        return $this->errorHandling['retry_wait_time'];
    }
}
