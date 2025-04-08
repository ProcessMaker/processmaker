<?php

namespace Tests\Feature\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use ProcessMaker\Cache\Settings\SettingCacheException;
use ProcessMaker\Cache\Settings\SettingCacheFactory;
use ProcessMaker\Models\Setting;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class SettingCacheTest extends TestCase
{
    use RequestHelper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'is_administrator' => true,
        ]);
    }

    private function upgrade()
    {
        require_once base_path('upgrades/2023_11_30_185738_add_password_policies_settings.php');
        $upgrade = new \AddPasswordPoliciesSettings();
        $upgrade->up();
    }

    public static function trackQueries(): void
    {
        DB::enableQueryLog();
    }

    public static function flushQueryLog(): void
    {
        DB::flushQueryLog();
    }

    public static function getQueriesExecuted(): array
    {
        return DB::getQueryLog();
    }

    public static function getQueryCount(): int
    {
        return count(self::getQueriesExecuted());
    }

    public function testGetSettingByKeyCached(): void
    {
        $this->upgrade();

        $key = 'password-policies.users_can_change';
        $cacheKey = 'setting_password-policies.users_can_change';
        $setting = Setting::where('key', $key)->first();
        \SettingCache::set($cacheKey, $setting);

        $this->trackQueries();

        $setting = Setting::byKey($key);

        $this->assertEquals(0, self::getQueryCount());
        $this->assertEquals($key, $setting->key);
    }

    public function testGetSettingByKeyNotCached(): void
    {
        $key = 'password-policies.uppercase';

        $this->upgrade();
        $this->trackQueries();

        $setting = Setting::byKey($key);

        $this->assertEquals(1, self::getQueryCount());
        $this->assertEquals($key, $setting->key);

        $this->flushQueryLog();

        $setting = Setting::byKey($key);
        $this->assertEquals(0, self::getQueryCount());
        $this->assertNotNull($setting);
        $this->assertEquals($key, $setting->key);
    }

    public function testGetSettingByKeyCachedAfterUpdate(): void
    {
        $key = 'password-policies.special';

        $this->upgrade();
        $this->trackQueries();

        $setting = Setting::byKey($key);

        $this->assertEquals(1, self::getQueryCount());
        $this->assertEquals($key, $setting->key);
        $this->assertEquals($setting->config, 1);

        $data = array_merge($setting->toArray(), ['config' => false]);

        $response = $this->apiCall('PUT', route('api.settings.update', ['setting' => $setting->id]), $data);
        $response->assertStatus(204);

        $this->flushQueryLog();

        $setting = Setting::byKey($key);
        $this->assertEquals(0, self::getQueryCount());
        $this->assertEquals($key, $setting->key);
        $this->assertEquals($setting->config, 0);
    }

    public function testGetSettingByNotExistingKey()
    {
        $this->withoutExceptionHandling();
        $key = 'non-existing-key';

        $callback = fn () => Setting::where('key', $key)->first();

        $this->expectException(\InvalidArgumentException::class);
        $setting = \SettingCache::getOrCache($key, $callback);

        $this->assertNull($setting);
    }

    public function testClearByPattern()
    {
        Cache::store('cache_settings')->put('password-policies.users_can_change', 1);
        Cache::store('cache_settings')->put('password-policies.numbers', 2);
        Cache::store('cache_settings')->put('password-policies.uppercase', 3);
        Cache::put('session-control.ip_restriction', 0);

        $this->assertEquals(1, Cache::store('cache_settings')->get('password-policies.users_can_change'));
        $this->assertEquals(2, Cache::store('cache_settings')->get('password-policies.numbers'));
        $this->assertEquals(3, Cache::store('cache_settings')->get('password-policies.uppercase'));

        $pattern = 'password-policies';

        \SettingCache::clearBy($pattern);

        $this->assertNull(Cache::store('cache_settings')->get('password-policies.users_can_change'));
        $this->assertNull(Cache::store('cache_settings')->get('password-policies.numbers'));
        $this->assertNull(Cache::store('cache_settings')->get('password-policies.uppercase'));
    }

    public function testClearByPatternRemainUnmatched()
    {
        Cache::store('cache_settings')->put('session-control.ip_restriction', 0);
        Cache::store('cache_settings')->put('password-policies.users_can_change', 1);
        Cache::store('cache_settings')->put('password-policies.numbers', 2);
        Cache::store('cache_settings')->put('password-policies.uppercase', 3);

        $this->assertEquals(0, Cache::store('cache_settings')->get('session-control.ip_restriction'));
        $this->assertEquals(1, Cache::store('cache_settings')->get('password-policies.users_can_change'));
        $this->assertEquals(2, Cache::store('cache_settings')->get('password-policies.numbers'));
        $this->assertEquals(3, Cache::store('cache_settings')->get('password-policies.uppercase'));

        $pattern = 'password-policies';

        \SettingCache::clearBy($pattern);

        $this->assertEquals(0, Cache::store('cache_settings')->get('session-control.ip_restriction'));
        $this->assertNull(Cache::store('cache_settings')->get('password-policies.users_can_change'));
        $this->assertNull(Cache::store('cache_settings')->get('password-policies.numbers'));
        $this->assertNull(Cache::store('cache_settings')->get('password-policies.uppercase'));
    }

    public function testClearByPatternWithFailedDeletion()
    {
        $pattern = 'test_pattern';
        $keys = [
            'settings:test_pattern:1',
            'settings:test_pattern:2',
        ];
        \SettingCache::set('test_pattern:1', 1);
        \SettingCache::set('test_pattern:2', 2);

        // Set up the expectation for the connection method
        Redis::shouldReceive('connection')
            ->with('cache_settings')
            ->andReturnSelf();

        Redis::shouldReceive('keys')
            ->with('phpunit-settings:*')
            ->andReturn($keys);

        Redis::shouldReceive('del')
            ->with($keys)
            ->andThrow(new SettingCacheException('Failed to delete keys.'));

        $this->expectException(SettingCacheException::class);
        $this->expectExceptionMessage('Failed to delete keys.');

        \SettingCache::clearBy($pattern);
    }

    public function testClearByPatternWithRedisPrefix()
    {
        Cache::store('cache_settings')->put('password-policies.users_can_change', 1);

        $this->assertEquals(1, Cache::store('cache_settings')->get('password-policies.users_can_change'));

        $pattern = 'password-policies';

        \SettingCache::clearBy($pattern);

        $this->assertNull(Cache::store('cache_settings')->get('password-policies.users_can_change'));
    }

    public function testClearAllSettings()
    {
        \SettingCache::set('password-policies.users_can_change', 1);
        \SettingCache::set('password-policies.numbers', 2);
        \SettingCache::set('password-policies.uppercase', 3);

        $this->assertEquals(1, \SettingCache::get('password-policies.users_can_change'));
        $this->assertEquals(2, \SettingCache::get('password-policies.numbers'));
        $this->assertEquals(3, \SettingCache::get('password-policies.uppercase'));

        \SettingCache::clear();

        $this->assertNull(\SettingCache::get('password-policies.users_can_change'));
        $this->assertNull(\SettingCache::get('password-policies.numbers'));
        $this->assertNull(\SettingCache::get('password-policies.uppercase'));
    }

    public function testClearOnlySettings()
    {
        \SettingCache::set('password-policies.users_can_change', 1);
        \SettingCache::set('password-policies.numbers', 2);

        Cache::store('file')->put('password-policies.uppercase', 3);

        $this->assertEquals(1, \SettingCache::get('password-policies.users_can_change'));
        $this->assertEquals(2, \SettingCache::get('password-policies.numbers'));

        $this->assertEquals(3, Cache::store('file')->get('password-policies.uppercase'));

        \SettingCache::clear();

        $this->assertNull(\SettingCache::get('password-policies.users_can_change'));
        $this->assertNull(\SettingCache::get('password-policies.numbers'));

        $this->assertEquals(3, Cache::store('file')->get('password-policies.uppercase'));
    }

    public function testInvalidateOnSaved()
    {
        $settingKey = 'password-policies.users_can_change';

        $setting = Setting::factory()->create([
            'key' => $settingKey,
            'config' => 1,
            'format' => 'boolean',
        ]);

        // Set the setting in the cache
        $settingCache = SettingCacheFactory::getSettingsCache();
        $settingCacheKey = $settingCache->createKey([
            'key' => $settingKey,
        ]);

        $settingCache->set($settingCacheKey, $setting);

        // Get the setting from the cache
        $settingFromCache = $settingCache->get($settingCacheKey);
        // Check if the setting is in the cache
        $this->assertEquals(1, $settingFromCache->config);

        // Update the setting to invalidate the cache
        $setting->update(['config' => 0]);
        // Get the setting from the cache
        $settingFromCache = $settingCache->get($settingCacheKey);
        // Check if the setting is invalidated
        $this->assertNull($settingFromCache);
    }

    public function testInvalidateOnDeleted()
    {
        $settingKey = 'password-policies.users_can_change';

        $setting = Setting::factory()->create([
            'key' => $settingKey,
            'config' => 1,
            'format' => 'boolean',
        ]);

        // Set the setting in the cache
        $settingCache = SettingCacheFactory::getSettingsCache();
        $settingCacheKey = $settingCache->createKey([
            'key' => $settingKey,
        ]);

        $settingCache->set($settingCacheKey, $setting);
        // Get the setting from the cache
        $settingFromCache = $settingCache->get($settingCacheKey);
        // Check if the setting is in the cache
        $this->assertEquals(1, $settingFromCache->config);

        // Delete the setting to invalidate the cache
        $setting->delete();
        // Get the setting from the cache
        $settingFromCache = $settingCache->get($settingCacheKey);
        // Check if the setting is invalidated
        $this->assertNull($settingFromCache);
    }

    public function testInvalidateWithException()
    {
        $setting = Setting::factory()->create([
            'key' => 'password-policies.numbers',
            'config' => 1,
            'format' => 'boolean',
        ]);

        \SettingCache::set($setting->key, $setting);
        $settingCache = \SettingCache::get($setting->key);

        $this->assertEquals(1, $settingCache->config);

        \SettingCache::shouldReceive('invalidate')
            ->with(['key' => $setting->key])
            ->andThrow(new SettingCacheException('Failed to invalidate cache KEY:' . $setting->key))
            ->once();

        $this->expectException(SettingCacheException::class);
        $this->expectExceptionMessage('Failed to invalidate cache KEY:' . $setting->key);
        \SettingCache::invalidate(['key' => $setting->key]);

        $setting->delete();
    }

    public function testDoNotQueryDatabaseForNullValues()
    {
        $key = 'password-policies.users_can_change';
        $cacheKey = 'setting_' . $key;

        \SettingCache::set($cacheKey, null);

        $this->trackQueries();

        $setting = Setting::byKey($key);

        $this->assertEquals(0, self::getQueryCount());
        $this->assertNull($setting);
    }

    public function testQueryDatabaseIfKeyIsNotCached()
    {
        $setting = Setting::factory()->create([
            'key' => 'key_not_cached',
            'config' => 1,
            'format' => 'boolean',
        ]);

        $this->trackQueries();

        $settingFromCache = Setting::byKey($setting->key);

        $this->assertEquals(1, self::getQueryCount());
        $this->assertNotNull($settingFromCache);
    }
}
