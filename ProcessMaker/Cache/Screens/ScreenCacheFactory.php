<?php

namespace ProcessMaker\Cache\Screens;

use Illuminate\Support\Facades\Config;
use ProcessMaker\Cache\Screens\ScreenCacheManager;
use ProcessMaker\Managers\ScreenCompiledManager;

class ScreenCacheFactory
{
    /**
     * Create a screen cache handler based on configuration
     *
     * @return ScreenCacheInterface
     */
    public static function create(): ScreenCacheInterface
    {
        $manager = Config::get('screens.cache.manager', 'legacy');

        if ($manager === 'new') {
            return app(ScreenCacheManager::class);
        }

        // Get the concrete ScreenCompiledManager instance from the container
        $compiledManager = app()->make(ScreenCompiledManager::class);

        return new LegacyScreenCacheAdapter($compiledManager);
    }
}
