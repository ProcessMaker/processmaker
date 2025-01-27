<?php

namespace ProcessMaker\Cache\Settings;

use Illuminate\Cache\CacheManager;
use ProcessMaker\Cache\AbstractCacheFactory;
use ProcessMaker\Cache\CacheInterface;

class SettingCacheFactory extends AbstractCacheFactory
{
    /**
     * Create the specific settings cache instance
     *
     * @param CacheManager $cacheManager
     * @return CacheInterface
     */
    protected static function createInstance(CacheManager $cacheManager): CacheInterface
    {
        return new SettingCacheManager($cacheManager);
    }

    /**
     * Get the current settings cache instance
     *
     * @return CacheInterface
     */
    public static function getSettingsCache(): CacheInterface
    {
        return static::getInstance();
    }
}
