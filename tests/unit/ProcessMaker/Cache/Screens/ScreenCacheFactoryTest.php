<?php

namespace Tests\Unit\ProcessMaker\Cache\Screens;

use Illuminate\Support\Facades\Config;
use ProcessMaker\Cache\Screens\LegacyScreenCacheAdapter;
use ProcessMaker\Cache\Screens\ScreenCacheFactory;
use ProcessMaker\Cache\Screens\ScreenCacheManager;
use ProcessMaker\Managers\ScreenCompiledManager;
use Tests\TestCase;

class ScreenCacheFactoryTest extends TestCase
{
    /** @test */
    public function it_creates_new_cache_manager_when_configured()
    {
        Config::set('screens.cache.manager', 'new');

        $cacheHandler = ScreenCacheFactory::create();

        $this->assertInstanceOf(ScreenCacheManager::class, $cacheHandler);
    }

    /** @test */
    public function it_creates_legacy_adapter_by_default()
    {
        Config::set('screens.cache.manager', 'legacy');

        $cacheHandler = ScreenCacheFactory::create();

        $this->assertInstanceOf(LegacyScreenCacheAdapter::class, $cacheHandler);
    }

    /** @test */
    public function it_creates_legacy_adapter_when_config_missing()
    {
        Config::set('screens.cache.manager', null);

        $cacheHandler = ScreenCacheFactory::create();

        $this->assertInstanceOf(LegacyScreenCacheAdapter::class, $cacheHandler);
    }
}
