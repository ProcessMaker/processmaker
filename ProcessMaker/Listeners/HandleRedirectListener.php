<?php

namespace ProcessMaker\Listeners;

use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Services\RedirectToEventService;

class HandleRedirectListener
{
    public function __construct(
        private ?RedirectToEventService $redirectToEventService = null
    ) {
    }

    protected function setRedirectTo(ProcessRequest $processRequest, string $method, ...$params): void
    {
        $this->redirectToEventService ??= app(RedirectToEventService::class);
        $this->redirectToEventService->setRedirectTo($processRequest, $method, ...$params);
    }
}
