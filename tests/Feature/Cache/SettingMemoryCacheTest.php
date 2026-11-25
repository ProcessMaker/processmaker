<?php

namespace Tests\Feature\Cache;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use ProcessMaker\Cache\Settings\SettingCacheFactory;
use ProcessMaker\Cache\Settings\SettingCacheManager;
use ProcessMaker\Cache\Settings\SettingCacheVersion;
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
        $this->seedEpoch(0);
    }

    protected function tearDown(): void
    {
        SettingMemoryCache::clearAllLocal();
        parent::tearDown();
    }

    private function seedEpoch(int $epoch): void
    {
        $prefix = config('cache.stores.cache_settings.prefix', 'settings:');
        $connection = config('cache.stores.cache_settings.connection', 'cache_settings');
        Redis::connection($connection)->set($prefix . SettingMemoryCache::EPOCH_KEY, (string) $epoch);
    }

    private function remoteEpoch(): int
    {
        $prefix = config('cache.stores.cache_settings.prefix', 'settings:');
        $connection = config('cache.stores.cache_settings.connection', 'cache_settings');

        return (int) Redis::connection($connection)->get($prefix . SettingMemoryCache::EPOCH_KEY);
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

        $first = Setting::byKey($setting->key);
        $this->assertNotNull($first);
        $this->assertTrue(SettingMemoryCache::exists($cacheKey));

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

        $cache->set($cacheKey, $setting);
        $this->assertTrue(SettingMemoryCache::exists($cacheKey));

        SettingMemoryCache::clearAllLocal();
        Cache::store('cache_settings')->put($cacheKey, [
            '__pm_setting' => true,
            'v' => $setting,
        ]);
        SettingMemoryCache::put($cacheKey, $setting);

        $reflected = new \ReflectionClass(SettingMemoryCache::class);
        $localEpochs = $reflected->getProperty('localEpochs');
        $localEpochs->setAccessible(true);
        $localEpochs->setValue(null, [SettingMemoryCache::currentTenantKey() => 0]);

        SettingMemoryCache::beginRequest();
        Setting::byKey($setting->key);

        $this->assertTrue(SettingMemoryCache::exists($cacheKey));
        $this->assertSame($this->remoteEpoch(), SettingMemoryCache::localEpoch());
    }

    public function testSetIncrementsSharedEpoch(): void
    {
        /** @var SettingCacheManager $cache */
        $cache = SettingCacheFactory::getSettingsCache();
        $this->seedEpoch(5);
        SettingMemoryCache::beginRequest();
        SettingMemoryCache::syncEpoch();
        $this->assertSame(5, SettingMemoryCache::localEpoch());

        $cache->set('setting_any.key', 'value');

        $this->assertSame(6, $this->remoteEpoch());
        $this->assertSame(6, SettingMemoryCache::localEpoch());
    }

    public function testInvalidateWithoutBumpDoesNotAdvanceEpoch(): void
    {
        /** @var SettingCacheManager $cache */
        $cache = SettingCacheFactory::getSettingsCache();
        $this->seedEpoch(5);
        SettingMemoryCache::beginRequest();
        SettingMemoryCache::syncEpoch();

        $cache->invalidate(['key' => 'setting_any.key']);

        $this->assertSame(5, $this->remoteEpoch());
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
        $this->assertGreaterThan(0, $this->remoteEpoch());
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
        $this->seedEpoch(1);
        SettingMemoryCache::syncEpoch();
        SettingMemoryCache::put('setting_hot', 'hot-value');

        (new ResetRequestState())->handle();

        SettingMemoryCache::syncEpoch();
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

        $requestOne = Request::create('/login', 'GET');
        app()->instance('request', $requestOne);
        Setting::byKey($setting->key);
        $this->assertTrue(SettingMemoryCache::exists($cacheKey));
        $this->assertSame('old-color', SettingMemoryCache::get($cacheKey)->config);

        $setting->config = 'new-color';
        $setting->saveQuietly();
        $cache->set($cacheKey, $setting->fresh());

        $requestTwo = Request::create('/login', 'GET');
        $this->assertNotSame(spl_object_id($requestOne), spl_object_id($requestTwo));
        app()->instance('request', $requestTwo);

        $resolved = Setting::byKey($setting->key);

        $this->assertNotNull($resolved);
        $this->assertSame('new-color', $resolved->config);
        $this->assertSame('new-color', SettingMemoryCache::get($cacheKey)->config);
    }

    public function testCachedNullDoesNotHitDatabaseAcrossEpochFlush(): void
    {
        $key = 'l1.null.cached';
        $cacheKey = 'setting_' . $key;

        /** @var SettingCacheManager $cache */
        $cache = SettingCacheFactory::getSettingsCache();
        $cache->set($cacheKey, null);

        // New request scope + matching epoch still serves null from Redis envelope (no DB).
        SettingMemoryCache::clearAllLocal();
        SettingMemoryCache::beginRequest();

        DB::enableQueryLog();
        DB::flushQueryLog();

        $setting = Setting::byKey($key);

        $this->assertNull($setting);
        $this->assertCount(0, DB::getQueryLog());
    }

    public function testSavedPublishesEpochAfterCommitNotDuringSaving(): void
    {
        $setting = Setting::factory()->create([
            'key' => 'l1.race.setting',
            'config' => 'old',
            'format' => 'text',
        ]);

        /** @var SettingCacheManager $cache */
        $cache = SettingCacheFactory::getSettingsCache();
        $cacheKey = $cache->createKey(['key' => $setting->key]);
        $cache->set($cacheKey, $setting);
        $epochAfterSet = $this->remoteEpoch();

        $setting->config = 'new';
        $setting->save();

        // saved() publishes epoch after commit
        $this->assertGreaterThan($epochAfterSet, $this->remoteEpoch());

        // Redis key dropped by saved(); committed value comes from DB until set().
        SettingMemoryCache::clearAllLocal();
        SettingMemoryCache::beginRequest();
        $resolved = Setting::byKey($setting->key);
        $this->assertSame('new', $resolved->config);
    }

    public function testJobBeginRequestResyncsEpoch(): void
    {
        /** @var SettingCacheManager $cache */
        $cache = SettingCacheFactory::getSettingsCache();
        $this->seedEpoch(1);
        SettingMemoryCache::syncEpoch();
        SettingMemoryCache::put('setting_job', 'stale');

        // Simulate Horizon starting another job (Queue::before → beginRequest).
        SettingMemoryCache::beginRequest();
        $this->seedEpoch(2);

        SettingMemoryCache::syncEpoch();
        $this->assertFalse(SettingMemoryCache::exists('setting_job'));
        $this->assertSame(2, SettingMemoryCache::localEpoch());
    }

    public function testSetStampsEnvelopeWithPerKeyGeneration(): void
    {
        /** @var SettingCacheManager $cache */
        $cache = SettingCacheFactory::getSettingsCache();
        $this->seedEpoch(3);
        SettingMemoryCache::beginRequest();
        SettingMemoryCache::syncEpoch();

        $cache->set('setting_stamp.gen', 'committed');

        $stored = Cache::store('cache_settings')->get('setting_stamp.gen');
        $this->assertIsArray($stored);
        $this->assertTrue($stored['__pm_setting'] ?? false);
        $this->assertSame('committed', $stored['v']);
        $this->assertSame(1, $stored['g']);
        $this->assertSame(4, $this->remoteEpoch());
    }

    public function testStaleLowerGenerationEnvelopeIsIgnoredOnResolve(): void
    {
        $setting = Setting::factory()->create([
            'key' => 'l1.stale.envelope',
            'config' => 'committed',
            'format' => 'text',
        ]);

        /** @var SettingCacheManager $cache */
        $cache = SettingCacheFactory::getSettingsCache();
        $cacheKey = $cache->createKey(['key' => $setting->key]);

        $cache->set($cacheKey, $setting->fresh());
        $committedGeneration = Cache::store('cache_settings')->get($cacheKey)['g'];
        $this->assertGreaterThan(0, $committedGeneration);

        // Simulate a lost resolve() race: pre-commit row written under the prior
        // generation after set()/saved() already advanced the per-key version.
        $stale = $setting->fresh();
        $stale->config = 'pre-commit';
        Cache::store('cache_settings')->put($cacheKey, [
            '__pm_setting' => true,
            'v' => $stale,
            'g' => $committedGeneration - 1,
        ]);

        SettingMemoryCache::clearAllLocal();
        SettingMemoryCache::beginRequest();

        $resolved = Setting::byKey($setting->key);

        $this->assertNotNull($resolved);
        $this->assertSame('committed', $resolved->config);

        $stored = Cache::store('cache_settings')->get($cacheKey);
        $this->assertSame('committed', $stored['v']->config);
        $this->assertSame($committedGeneration, $stored['g']);
    }

    public function testLegacyEnvelopeWithoutGenerationIsTreatedAsMiss(): void
    {
        $setting = Setting::factory()->create([
            'key' => 'l1.legacy.envelope',
            'config' => 'from-db',
            'format' => 'text',
        ]);

        /** @var SettingCacheManager $cache */
        $cache = SettingCacheFactory::getSettingsCache();
        $cacheKey = $cache->createKey(['key' => $setting->key]);
        $generation = SettingCacheVersion::get($cacheKey);

        $this->seedEpoch(7);
        SettingMemoryCache::beginRequest();
        SettingMemoryCache::syncEpoch();

        $stale = $setting->fresh();
        $stale->config = 'legacy-cache';
        Cache::store('cache_settings')->put($cacheKey, [
            '__pm_setting' => true,
            'v' => $stale,
        ]);

        $resolved = Setting::byKey($setting->key);

        $this->assertSame('from-db', $resolved->config);
        $stored = Cache::store('cache_settings')->get($cacheKey);
        $this->assertSame($generation, $stored['g']);
        $this->assertSame('from-db', $stored['v']->config);
    }

    public function testSetOnOneKeyDoesNotInvalidateOtherKeyEnvelopes(): void
    {
        /** @var SettingCacheManager $cache */
        $cache = SettingCacheFactory::getSettingsCache();

        $cache->set('setting_multi.a', 'alpha');
        $cache->set('setting_multi.b', 'beta');

        SettingMemoryCache::clearAllLocal();
        SettingMemoryCache::beginRequest();

        $this->assertSame('alpha', $cache->get('setting_multi.a'));
        $this->assertSame('beta', $cache->get('setting_multi.b'));
    }
}
