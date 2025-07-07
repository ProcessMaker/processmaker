<?php

namespace Tests\Repositories;

use Illuminate\Cache\CacheManager;
use Illuminate\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use ProcessMaker\Cache\Settings\SettingCacheFactory;
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
        // "collections.<id>.obfuscate" is a key generated in "Records" model in collection package
        Setting::create([
            'key' => 'collections.6.obfuscate',
            'config' => 'foo',
            'format' => 'text',
        ]);

        $this->assertEquals('foo', config('collections.6.obfuscate'));

        // Update the setting directly in DB and Redis Cache to simulate external change
        Setting::where('key', 'collections.6.obfuscate')->update(['config' => 'bar']);
        $settingCache = SettingCacheFactory::getSettingsCache();
        $settingCache->clear();

        // Should still return Class cached value, not updated one
        $this->assertEquals('foo', config('collections.6.obfuscate'));
    }
}
