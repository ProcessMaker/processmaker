<?php

declare(strict_types=1);

namespace ProcessMaker\Octane;

use Lavary\Menu\Menu;
use ProcessMaker\Listeners\HandleRedirectListener;
use ProcessMaker\Managers\MenuManager;
use ProcessMaker\Providers\ProcessMakerServiceProvider;

final class ResetRequestState
{
    public function handle(): void
    {
        ProcessMakerServiceProvider::beginRequestTiming();
        HandleRedirectListener::reset();

        $menuManager = app(Menu::class);
        if ($menuManager instanceof MenuManager) {
            $menuManager->reset();
        }
    }
}
