<?php

namespace ProcessMaker\Listeners;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Events\ProcessCompleted;
use ProcessMaker\Models\ProcessRequest;

class HandleEndEventRedirect extends HandleRedirectListener
{
    /**
     * Handle the process completed event.
     *
     * This method processes the event by:
     * 1. Validating the event is not null
     * 2. Getting and validating the process request
     * 3. Checking if it's a subprocess (skips if true)
     * 4. Handling the main process redirect
     *
     * @param ProcessCompleted|null $event The process completed event
     * @return void
     */
    public function handle(?ProcessCompleted $event): void
    {
        if (!$event) {
            Log::error('Null event passed to HandleEndEventRedirect');

            return;
        }

        try {
            $request = $this->validateAndGetRequest($event);
            if (!$request) {
                return;
            }

            if ($this->isSubprocess($request)) {
                Log::debug('Skipping subprocess redirect');

                return;
            }

            $this->handleMainProcessRedirect($request, $event);
        } catch (\Throwable $e) {
            $this->logError($e);
        }
    }

    /**
     * Validates and returns the process request from the event
     */
    private function validateAndGetRequest(ProcessCompleted $event): ?ProcessRequest
    {
        $request = $event->getProcessRequest();

        if (!$request instanceof ProcessRequest) {
            Log::info('Invalid or empty request in HandleEndEventRedirect');

            return null;
        }

        if (!$request->id) {
            Log::warning('Request has no ID in HandleEndEventRedirect');

            return null;
        }

        return $request;
    }

    /**
     * Logs error details
     */
    private function logError(\Throwable $e): void
    {
        Log::error('Error in HandleEndEventRedirect: ' . $e->getMessage(), [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
    }

    /**
     * Check if the request is a subprocess
     *
     * @param ProcessRequest|null $request
     * @return bool
     */
    private function isSubprocess(?ProcessRequest $request): bool
    {
        if (!$request) {
            Log::warning('Null request passed to isSubprocess check');

            return false;
        }

        return !empty($request->parent_request_id);
    }

    /**
     * Handle the redirection for the main process
     *
     * @param ProcessRequest $request
     * @param ProcessCompleted $event
     * @return void
     */
    private function handleMainProcessRedirect(ProcessRequest $request, ProcessCompleted $event): void
    {
        // Type hints ensure $request and $event are valid, no need for null check
        $userId = Auth::id();
        $requestId = $request->id;

        if ($userId) {
            try {
                $this->setRedirectTo($request, 'processCompletedRedirect', $event, $userId, $requestId);
            } catch (\Throwable $e) {
                $this->logError($e);
            }
        } else {
            Log::warning('No authenticated user found when handling end event redirect', [
                'request_id' => $requestId,
            ]);
        }
    }
}
