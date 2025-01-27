<?php

namespace ProcessMaker\Cache;

use Illuminate\Cache\CacheManager;
use ProcessMaker\Cache\Monitoring\CacheMetricsInterface;

interface CacheFactoryInterface
{
    /**
     * Create a new cache instance with metrics monitoring
     *
     * @param CacheManager $cacheManager
     * @param CacheMetricsInterface $metrics
     * @return CacheInterface
     */
    public static function create(CacheManager $cacheManager, CacheMetricsInterface $metrics): CacheInterface;
}
