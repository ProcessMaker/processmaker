<?php

namespace ProcessMaker\Services;

use ProcessMaker\Events\RedirectToEvent;
use ProcessMaker\Models\ProcessRequest;

/**
 * Collects and dispatches the redirect selected during a workflow operation.
 *
 * The service is container-scoped so pending redirect data is isolated to the
 * current HTTP request or queue job. When several workflow events request a
 * redirect, the most recently recorded redirect replaces the previous one.
 */
class RedirectToEventService
{
    private ?ProcessRequest $processRequest = null;

    private string $redirectionMethod = '';

    private array $redirectionParams = [];

    /**
     * Record the redirect to dispatch when the current workflow operation ends.
     *
     * Calling this method again before dispatch replaces all previously pending
     * redirect state, preserving the existing "last redirect wins" behavior.
     *
     * @param ProcessRequest $processRequest Request whose channels receive the redirect
     * @param string $method Client-side redirect method to invoke
     * @param mixed ...$params Ordered arguments passed to the redirect event
     */
    public function setRedirectTo(ProcessRequest $processRequest, string $method, ...$params): void
    {
        $this->processRequest = $processRequest;
        $this->redirectionMethod = $method;
        $this->redirectionParams = $params;
    }

    /**
     * Dispatch the pending redirect, including the request's active token IDs.
     *
     * This method is a no-op when no redirect is pending. Pending state is
     * consumed before querying tokens or dispatching the event so an exception
     * cannot cause stale request data to be retried or leaked into later work.
     *
     * @throws \Throwable If active-token retrieval or event dispatch fails
     */
    public function sendRedirectToEvent(): void
    {
        if ($this->processRequest === null) {
            return;
        }

        $processRequest = $this->processRequest;
        $method = $this->redirectionMethod;
        $params = $this->redirectionParams;

        // Consume the pending redirect before doing work that may throw.
        $this->reset();

        $params['activeTokens'] = ProcessRequest::getActiveTokens($processRequest);
        event(new RedirectToEvent($processRequest, $method, $params));
    }

    /**
     * Discard all pending redirect state without dispatching an event.
     */
    public function reset(): void
    {
        $this->processRequest = null;
        $this->redirectionMethod = '';
        $this->redirectionParams = [];
    }
}
