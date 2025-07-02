<?php

namespace Tests\Repositories;

use Illuminate\Cache\CacheManager;
use Illuminate\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use ProcessMaker\Models\Setting;
use Tests\TestCase;

class SettingsConfigRepositoryTest extends TestCase
{
    public function testDotNotation()
    {
        Setting::create([
            'key' => 'test',
            'config' => '{"dot":{"notation":"the value"}}',
            'format' => 'array',
        ]);

        $this->assertEquals('the value', config('test.dot.notation'));
    }

    public function testCachesValueSimple()
    {
        Setting::create([
            'key' => 'cache-test',
            'config' => 'foo',
            'format' => 'text',
        ]);

        $this->assertEquals('foo', config('cache-test'));

        // Update the setting directly in DB and Redis Cache to simulate external change
        Setting::where('key', 'cache-test')->update(['config' => 'bar']);
        $settingCache = SettingCacheFactory::getSettingsCache();
        $settingCache->clear();

        // Should still return Class cached value, not updated one
        $this->assertEquals('foo', config('cache-test'));
    }
}
