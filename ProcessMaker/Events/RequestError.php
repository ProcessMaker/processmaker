<?php

namespace ProcessMaker\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\ProcessRequest;

class RequestError implements SecurityLogEventInterface
{
    use Dispatchable;

    private ProcessRequest $data;

    private string $error;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(ProcessRequest $data, string $error)
    {
        $this->data = $data;
        $this->error = $error;
    }

    /**
     * Return event data
     */
    public function getData(): array
    {
        return [
            'request' => [
                'label' => $this->data->getAttribute('id'),
                'link' => route('requests.show', $this->data),
            ],
            'error' => $this->error,
            'occurred_at' => Carbon::now(),
        ];
    }

    /**
     * Return event changes
     */
    public function getChanges(): array
    {
        return [];
    }

    /**
     * return event name
     */
    public function getEventName(): string
    {
        return 'RequestError';
    }

    /**
     * Dispatch the event if the process request is not rate limited
     */
    public static function dispatchIfNotRateLimited(ProcessRequest $request, string $error)
    {
        $key = 'process-request-errors:' . $request->getKey();
        $limit= config('app.process_request_errors_rate_limit', 1);
        Log::info("Rate limit is set to {$limit} for process request errors.");

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            Log::warning("Process {$request->id} has reached the request error limit for today.");
            return false;
        }

        $duration = config('app.process_request_errors_rate_limit_duration', 86400);
        Log::info("Rate limit duration is set to {$duration} for process request errors.");
        RateLimiter::hit($key, $duration);
        event(new static($request, $error));

        return true;
    }
}
