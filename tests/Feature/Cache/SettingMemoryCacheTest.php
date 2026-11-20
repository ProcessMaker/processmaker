<?php

namespace Tests\Feature\Cache;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Cache\Settings\SettingCacheFactory;
use ProcessMaker\Cache\Settings\SettingCacheManager;
use ProcessMaker\Cache\Settings\SettingMemoryCache;
use ProcessMaker\Models\Setting;
use ProcessMaker\Octane\ResetRequestState;
use Tests\TestCase;

class SettingMemoryCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        SettingMemoryCache::clearAllLocal();
        SettingMemoryCache::beginRequest();
        Cache::store('cache_settings')->forget(SettingMemoryCache::EPOCH_KEY);
    }

    protected function tearDown(): void
    {
        SettingMemoryCache::clearAllLocal();
        parent::tearDown();
    }

    public function testL1HitAvoidsSecondRedisReadForSameKey(): void
    {
        $setting = Setting::factory()->create([
            'key' => 'l1.hit.test',
            'config' => true,
            'format' => 'boolean',
        ]);

        /** @var SettingCacheManager $cache */
        $cache = SettingCacheFactory::getSettingsCache();
        $cacheKey = $cache->createKey(['key' => $setting->key]);

        // Prime L2 + L1
        $first = Setting::byKey($setting->key);
        $this->assertNotNull($first);
        $this->assertTrue(SettingMemoryCache::exists($cacheKey));

        // Remove from Redis only; L1 should still serve the value.
        Cache::store('cache_settings')->forget($cacheKey);

        $second = Setting::byKey($setting->key);
        $this->assertNotNull($second);
        $this->assertEquals($setting->key, $second->key);
        $this->assertEquals($first->config, $second->config);
    }

    public function testIntraRequestMemoizationForRepeatedByKeyCalls(): void
    {
        $setting = Setting::factory()->create([
            'key' => 'l1.memo.test',
            'config' => ['value' => 1],
            'format' => 'object',
        ]);

        $cacheKey = SettingCacheFactory::getSettingsCache()->createKey(['key' => $setting->key]);

        Setting::byKey($setting->key);
        $this->assertTrue(SettingMemoryCache::exists($cacheKey));

        // Simulate many color()/byKey calls in one request — still L1.
        for ($i = 0; $i < 10; $i++) {
            $resolved = Setting::byKey($setting->key);
            $this->assertNotNull($resolved);
            $this->assertEquals($setting->key, $resolved->key);
        }
    }

    public function testEpochBumpClearsL1ForSubsequentRequest(): void
    {
        $setting = Setting::factory()->create([
            'key' => 'l1.epoch.test',
            'config' => 1,
            'format' => 'boolean',
        ]);

        /** @var SettingCacheManager $cache */
        $cache = SettingCacheFactory::getSettingsCache();
        $cacheKey = $cache->createKey(['key' => $setting->key]);

        Setting::byKey($setting->key);
        $this->assertTrue(SettingMemoryCache::exists($cacheKey));

        // Admin update path: invalidate bumps epoch and clears L1.
        $cache->invalidate(['key' => $cacheKey]);
        $this->assertFalse(SettingMemoryCache::exists($cacheKey));

        // Stale value left only in a "remote worker" simulation: put old value in L1
        // with an outdated local epoch, then begin a new request and sync.
        SettingMemoryCache::clearAllLocal();
        Cache::store('cache_settings')->put($cacheKey, $setting);
        SettingMemoryCache::put($cacheKey, $setting);
        // Force local epoch behind Redis without marking synced.
        $reflected = new \ReflectionClass(SettingMemoryCache::class);
        $localEpochs = $reflected->getProperty('localEpochs');
        $localEpochs->setAccessible(true);
        $localEpochs->setValue(null, [SettingMemoryCache::currentTenantKey() => 0]);

        SettingMemoryCache::beginRequest();
        Setting::byKey($setting->key);

        // After sync, L1 is rebuilt from Redis (not serving a stale unsynced map forever).
        $this->assertTrue(SettingMemoryCache::exists($cacheKey));
        $this->assertSame(
            (int) Cache::store('cache_settings')->get(SettingMemoryCache::EPOCH_KEY, 0),
            SettingMemoryCache::localEpoch()
        );
    }

    public function testInvalidateIncrementsSharedEpoch(): void
    {
        /** @var SettingCacheManager $cache */
        $cache = SettingCacheFactory::getSettingsCache();
        Cache::store('cache_settings')->forever(SettingMemoryCache::EPOCH_KEY, 5);
        SettingMemoryCache::beginRequest();
        SettingMemoryCache::syncEpoch($cache->store());
        $this->assertSame(5, SettingMemoryCache::localEpoch());

        $cache->invalidate(['key' => 'setting_any.key']);

        $this->assertSame(6, (int) Cache::store('cache_settings')->get(SettingMemoryCache::EPOCH_KEY));
        $this->assertSame(6, SettingMemoryCache::localEpoch());
    }

    public function testClearSeedsNewEpochAndClearsL1(): void
    {
        /** @var SettingCacheManager $cache */
        $cache = SettingCacheFactory::getSettingsCache();
        $cache->set('setting_clear.test', 'value');
        $this->assertTrue(SettingMemoryCache::exists('setting_clear.test'));

        $cache->clear();

        $this->assertFalse(SettingMemoryCache::exists('setting_clear.test'));
        $this->assertNull($cache->get('setting_clear.test'));
        $this->assertGreaterThan(0, (int) Cache::store('cache_settings')->get(SettingMemoryCache::EPOCH_KEY, 0));
    }

    public function testTenantScopesAreIsolatedInL1(): void
    {
        $cacheKey = 'setting_tenant.isolation';

        app()->instance('currentTenant', (object) ['id' => 1]);
        SettingMemoryCache::beginRequest();
        SettingMemoryCache::put($cacheKey, 'tenant-1-value');

        app()->instance('currentTenant', (object) ['id' => 2]);
        SettingMemoryCache::beginRequest();
        $this->assertFalse(SettingMemoryCache::exists($cacheKey));
        SettingMemoryCache::put($cacheKey, 'tenant-2-value');

        app()->instance('currentTenant', (object) ['id' => 1]);
        SettingMemoryCache::beginRequest();
        $this->assertTrue(SettingMemoryCache::exists($cacheKey));
        $this->assertSame('tenant-1-value', SettingMemoryCache::get($cacheKey));

        app()->instance('currentTenant', (object) ['id' => 2]);
        SettingMemoryCache::beginRequest();
        $this->assertSame('tenant-2-value', SettingMemoryCache::get($cacheKey));

        app()->forgetInstance('currentTenant');
    }

    public function testResetRequestStateAllowsEpochRecheckWithoutDroppingHotL1(): void
    {
        /** @var SettingCacheManager $cache */
        $cache = SettingCacheFactory::getSettingsCache();
        Cache::store('cache_settings')->forever(SettingMemoryCache::EPOCH_KEY, 1);
        SettingMemoryCache::syncEpoch($cache->store());
        SettingMemoryCache::put('setting_hot', 'hot-value');

        (new ResetRequestState())->handle();

        // Same epoch — L1 retained after beginRequest + sync.
        SettingMemoryCache::syncEpoch($cache->store());
        $this->assertTrue(SettingMemoryCache::exists('setting_hot'));
        $this->assertSame('hot-value', SettingMemoryCache::get('setting_hot'));
    }

    public function testNewHttpRequestResyncsEpochWithoutResetRequestState(): void
    {
        $setting = Setting::factory()->create([
            'key' => 'l1.fpm.stale',
            'config' => 'old-color',
            'format' => 'text',
        ]);

        /** @var SettingCacheManager $cache */
        $cache = SettingCacheFactory::getSettingsCache();
        $cacheKey = $cache->createKey(['key' => $setting->key]);

        // Worker A, request 1: warm L1.
        $requestOne = Request::create('/login', 'GET');
        app()->instance('request', $requestOne);
        Setting::byKey($setting->key);
        $this->assertTrue(SettingMemoryCache::exists($cacheKey));
        $this->assertSame('old-color', SettingMemoryCache::get($cacheKey)->config);

        // Another server updates the setting and bumps the shared epoch.
        // Simulate without calling beginRequest (FPM does not run ResetRequestState).
        $setting->config = 'new-color';
        $setting->saveQuietly();
        Cache::store('cache_settings')->forever($cacheKey, $setting->fresh());
        Cache::store('cache_settings')->forever(
            SettingMemoryCache::EPOCH_KEY,
            ((int) Cache::store('cache_settings')->get(SettingMemoryCache::EPOCH_KEY, 0)) + 1
        );

        // Worker A, request 2: new Request object must force epoch re-check.
        $requestTwo = Request::create('/login', 'GET');
        $this->assertNotSame(spl_object_id($requestOne), spl_object_id($requestTwo));
        app()->instance('request', $requestTwo);

        $resolved = Setting::byKey($setting->key);

        $this->assertNotNull($resolved);
        $this->assertSame('new-color', $resolved->config);
        $this->assertSame('new-color', SettingMemoryCache::get($cacheKey)->config);
    }

    public function testCachedNullDoesNotHitDatabase(): void
    {
        $key = 'l1.null.cached';
        $cacheKey = 'setting_' . $key;
        SettingCacheFactory::getSettingsCache()->set($cacheKey, null);

        DB::enableQueryLog();
        DB::flushQueryLog();

        $setting = Setting::byKey($key);

        $this->assertNull($setting);
        $this->assertCount(0, DB::getQueryLog());
    }
}
