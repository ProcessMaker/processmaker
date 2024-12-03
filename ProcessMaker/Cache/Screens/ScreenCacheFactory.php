<?php

namespace ProcessMaker\Cache\Screens;

use Illuminate\Support\Facades\Config;
use ProcessMaker\Cache\Screens\ScreenCacheManager;
use ProcessMaker\Managers\ScreenCompiledManager;

class ScreenCacheFactory
{
    private static ?ScreenCacheInterface $testInstance = null;

    /**
     * Set a test instance for the factory
     *
     * @param ScreenCacheInterface|null $instance
     */
    public static function setTestInstance(?ScreenCacheInterface $instance): void
    {
        self::$testInstance = $instance;
    }

    /**
     * Create a screen cache handler based on configuration
     *
     * @return ScreenCacheInterface
     */
    public static function create(): ScreenCacheInterface
    {
        if (self::$testInstance !== null) {
            return self::$testInstance;
        }

        $manager = Config::get('screens.cache.manager', 'legacy');

        if ($manager === 'new') {
            return app(ScreenCacheManager::class);
        }

        // Get the concrete ScreenCompiledManager instance from the container
        $compiledManager = app()->make(ScreenCompiledManager::class);

        return new LegacyScreenCacheAdapter($compiledManager);
    }
}
