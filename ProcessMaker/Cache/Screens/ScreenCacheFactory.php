<?php

namespace ProcessMaker\Cache\Screens;

use Illuminate\Cache\CacheManager;
use ProcessMaker\Cache\AbstractCacheFactory;
use ProcessMaker\Cache\CacheInterface;
use ProcessMaker\Cache\Screens\LegacyScreenCacheAdapter;
use ProcessMaker\Cache\Screens\ScreenCacheManager;
use ProcessMaker\Managers\ScreenCompiledManager;

class ScreenCacheFactory extends AbstractCacheFactory
{
    /**
     * Create the specific screen cache instance
     *
     * @param CacheManager $cacheManager
     * @return CacheInterface
     */
    protected static function createInstance(CacheManager $cacheManager): CacheInterface
    {
        $manager = config('screens.cache.manager', 'legacy');

        return $manager === 'new'
            ? app(ScreenCacheManager::class)
            : new LegacyScreenCacheAdapter(app()->make(ScreenCompiledManager::class));
    }

    /**
     * Get the current screen cache instance
     *
     * @return CacheInterface
     */
    public static function getScreenCache(): CacheInterface
    {
        return static::getInstance();
    }
}
