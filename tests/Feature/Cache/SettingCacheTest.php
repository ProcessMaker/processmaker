<?php

namespace Tests\Feature\Cache;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use ProcessMaker\Cache\Settings\SettingCacheException;
use ProcessMaker\Models\Setting;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class SettingCacheTest extends TestCase
{
    use RequestHelper;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'is_administrator' => true,
        ]);

        config()->set('cache.default', 'cache_settings');
    }

    protected function tearDown(): void
    {
        \SettingCache::clear();

        config()->set('cache.default', 'array');

        parent::tearDown();
    }

    private function upgrade()
    {
        $this->artisan('migrate', [
            '--path' => 'upgrades/2023_11_30_185738_add_password_policies_settings.php',
        ])->run();
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

        $setting = Setting::where('key', $key)->first();
        \SettingCache::set($key, $setting);

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

        $callback = fn() => Setting::where('key', $key)->first();

        $this->expectException(\InvalidArgumentException::class);
        $setting = \SettingCache::getOrCache($key, $callback);

        $this->assertNull($setting);
    }

    public function testClearByPattern()
    {
        \SettingCache::set('password-policies.users_can_change', 1);
        \SettingCache::set('password-policies.numbers', 2);
        \SettingCache::set('password-policies.uppercase', 3);
        Cache::put('session-control.ip_restriction', 0);

        $this->assertEquals(1, \SettingCache::get('password-policies.users_can_change'));
        $this->assertEquals(2, \SettingCache::get('password-policies.numbers'));
        $this->assertEquals(3, \SettingCache::get('password-policies.uppercase'));

        $pattern = 'password-policies';

        \SettingCache::clearBy($pattern);

        $this->assertNull(\SettingCache::get('password-policies.users_can_change'));
        $this->assertNull(\SettingCache::get('password-policies.numbers'));
        $this->assertNull(\SettingCache::get('password-policies.uppercase'));
    }

    public function testClearByPatternRemainUnmatched()
    {
        \SettingCache::set('session-control.ip_restriction', 0);
        \SettingCache::set('password-policies.users_can_change', 1);
        \SettingCache::set('password-policies.numbers', 2);
        \SettingCache::set('password-policies.uppercase', 3);

        $this->assertEquals(0, \SettingCache::get('session-control.ip_restriction'));
        $this->assertEquals(1, \SettingCache::get('password-policies.users_can_change'));
        $this->assertEquals(2, \SettingCache::get('password-policies.numbers'));
        $this->assertEquals(3, \SettingCache::get('password-policies.uppercase'));

        $pattern = 'password-policies';

        \SettingCache::clearBy($pattern);

        $this->assertEquals(0, \SettingCache::get('session-control.ip_restriction'));
        $this->assertNull(\SettingCache::get('password-policies.users_can_change'));
        $this->assertNull(\SettingCache::get('password-policies.numbers'));
        $this->assertNull(\SettingCache::get('password-policies.uppercase'));
    }

    public function testClearByPatternWithFailedDeletion()
    {
        $pattern = 'test_pattern';
        $keys = [
            'settings:test_pattern:1',
            'settings:test_pattern:2'
        ];
        \SettingCache::set('test_pattern:1', 1);
        \SettingCache::set('test_pattern:2', 2);

        Redis::shouldReceive('keys')
            ->with('*settings:*')
            ->andReturn($keys);

        Redis::shouldReceive('del')
            ->with($keys)
            ->andThrow(new SettingCacheException('Failed to delete keys.'));

        $this->expectException(SettingCacheException::class);
        $this->expectExceptionMessage('Failed to delete keys.');

        \SettingCache::clearBy($pattern);
    }

    public function testTryClearByPatternWithNonRedisDriver()
    {
        config()->set('cache.default', 'array');

        $this->expectException(SettingCacheException::class);
        $this->expectExceptionMessage('The cache driver must be Redis.');

        \SettingCache::clearBy('pattern');
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

        config()->set('cache.default', 'array');
        Cache::put('password-policies.uppercase', 3);

        config()->set('cache.default', 'cache_settings');
        $this->assertEquals(1, \SettingCache::get('password-policies.users_can_change'));
        $this->assertEquals(2, \SettingCache::get('password-policies.numbers'));

        config()->set('cache.default', 'array');
        $this->assertEquals(3, Cache::get('password-policies.uppercase'));

        config()->set('cache.default', 'cache_settings');
        \SettingCache::clear();

        $this->assertNull(\SettingCache::get('password-policies.users_can_change'));
        $this->assertNull(\SettingCache::get('password-policies.numbers'));

        config()->set('cache.default', 'array');
        $this->assertEquals(3, Cache::get('password-policies.uppercase'));
    }

    public function testInvalidateOnSaved()
    {
        $setting  = Setting::factory()->create([
            'key' => 'password-policies.users_can_change',
            'config' => 1,
            'format' => 'boolean',
        ]);

        \SettingCache::set($setting->key, $setting);
        $settingCache = \SettingCache::get($setting->key);

        $this->assertEquals(1, $settingCache->config);

        $setting->update(['config' => 0]);
        $settingCache = \SettingCache::get($setting->key);
        $this->assertNull($settingCache);
    }

    public function testInvalidateOnDeleted()
    {
        $setting  = Setting::factory()->create([
            'key' => 'password-policies.users_can_change',
            'config' => 1,
            'format' => 'boolean',
        ]);

        \SettingCache::set($setting->key, $setting);
        $settingCache = \SettingCache::get($setting->key);

        $this->assertEquals(1, $settingCache->config);

        $setting->delete();
        $settingCache = \SettingCache::get($setting->key);
        $this->assertNull($settingCache);
    }

    public function testInvalidateWithException()
    {
        $setting  = Setting::factory()->create([
            'key' => 'password-policies.numbers',
            'config' => 1,
            'format' => 'boolean',
        ]);

        \SettingCache::set($setting->key, $setting);
        $settingCache = \SettingCache::get($setting->key);

        $this->assertEquals(1, $settingCache->config);

        \SettingCache::shouldReceive('invalidate')
            ->with($setting->key)
            ->andThrow(new SettingCacheException('Failed to invalidate cache KEY:' . $setting->key))
            ->once();
        $this->expectException(SettingCacheException::class);
        $this->expectExceptionMessage('Failed to invalidate cache KEY:' . $setting->key);

        \SettingCache::shouldReceive('clear')->once()->andReturn(true);

        $setting->delete();
    }
}
