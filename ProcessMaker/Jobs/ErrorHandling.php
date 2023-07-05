<?php

namespace ProcessMaker\Jobs;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ScriptVersion;
use ProcessMaker\Notifications\ErrorExecutionNotification;

class ErrorHandling
{
    public $errorHandling = [];
    
    public static $callback;

    public function __construct(
        public $processRequestToken,
    )
    {
        
    }

    public function handleRetries($job, $exception)
    {
        $message = $exception->getMessage();

        if ($this->retryAttempts() > 0) {
            if ($job->attemptNum <= $this->retryAttempts()) {
                Log::info('Retry the job process. Attempt ' . $job->attemptNum . ' of ' . $this->retryAttempts() . ', Wait time: ' . $this->retryWaitTime());
                $this->requeue($job);

                return $message;
            }

            $message = __('Job failed after :attempts total attempts', ['attempts' => $job->attemptNum - 1]) . "\n" . $message;

            $this->sendExecutionErrorNotification($message);

            return $message;
        } else {
            $this->sendExecutionErrorNotification($message);
        }

        return $message;
    }

    private function requeue($job)
    {
        $class = get_class($job);
        $newJob = new $class(
            Process::findOrFail($job->definitionsId),
            ProcessRequest::findOrFail($job->instanceId),
            ProcessRequestToken::findOrFail($job->tokenId),
            $job->data,
            $job->attemptNum + 1
        );
        $newJob->delay($this->retryWaitTime());
        dispatch($newJob);
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
                Notification::send($user, new ErrorExecutionNotification($this->processRequestToken, $message, $this->errorHandling));
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
        Log::info('Setting errorHandling', ['value' => $value]);
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
