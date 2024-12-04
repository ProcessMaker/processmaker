<?php

namespace ProcessMaker\Cache\Screens;

use Illuminate\Support\Facades\Config;
use ProcessMaker\Cache\Monitoring\CacheMetricsDecorator;
use ProcessMaker\Cache\Monitoring\RedisMetricsManager;
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

        // Create the appropriate cache implementation
        $manager = Config::get('screens.cache.manager', 'legacy');
        $cache = $manager === 'new'
            ? app(ScreenCacheManager::class)
            : new LegacyScreenCacheAdapter(app()->make(ScreenCompiledManager::class));

        // If already wrapped with metrics decorator, return as is
        if ($cache instanceof CacheMetricsDecorator) {
            return $cache;
        }

        // Wrap with metrics decorator if not already wrapped
        return new CacheMetricsDecorator($cache, app()->make(RedisMetricsManager::class));
    }
}
