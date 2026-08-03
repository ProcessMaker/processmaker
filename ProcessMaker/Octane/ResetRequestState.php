<?php

declare(strict_types=1);

namespace ProcessMaker\Octane;

use ProcessMaker\Listeners\HandleRedirectListener;
use ProcessMaker\Models\FormalExpression;
use ProcessMaker\Providers\ProcessMakerServiceProvider;

final class ResetRequestState
{
    public function handle(): void
    {
        ProcessMakerServiceProvider::beginRequestTiming();
        HandleRedirectListener::reset();
        FormalExpression::resetPmFunctions();
    }
}
