<?php

declare(strict_types=1);

namespace ProcessMaker\Octane;

use ProcessMaker\ImportExport\Manifest;
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
        Manifest::resetRequestState();
        $this->redirectToEventService->reset();
    }
}
