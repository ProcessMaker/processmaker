<?php

declare(strict_types=1);

namespace ProcessMaker\Octane;

use ProcessMaker\Providers\ProcessMakerServiceProvider;
use ProcessMaker\Services\RedirectToEventService;

final class ResetRequestState
{
    public function __construct(
        private readonly RedirectToEventService $redirectToEventService
    ) {
    }

    public function handle(): void
    {
        ProcessMakerServiceProvider::beginRequestTiming();
        $this->redirectToEventService->reset();
    }
}
