<?php

declare(strict_types=1);

namespace ProcessMaker\Octane;

use ProcessMaker\Cache\Settings\SettingMemoryCache;
use ProcessMaker\Listeners\HandleRedirectListener;
use ProcessMaker\Providers\ProcessMakerServiceProvider;

final class ResetRequestState
{
    public function handle(): void
    {
        ProcessMakerServiceProvider::beginRequestTiming();
        HandleRedirectListener::reset();
        // Allow the settings L1 epoch to be re-checked once per request without
        // dropping the in-memory map when the epoch is unchanged.
        SettingMemoryCache::beginRequest();
    }
}
