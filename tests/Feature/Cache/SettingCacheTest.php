<?php

namespace Tests\Feature\Cache;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Models\Setting;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class SettingCacheTest extends TestCase
{
    use RequestHelper;
    use RefreshDatabase;

    /* public function setUp(): void
    {
        parent::setUp();
    } */

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
        \SettingCache::delete($key);

        $this->upgrade();
        $this->trackQueries();

        $setting = Setting::byKey($key, true);

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
        \SettingCache::delete($key);

        $this->upgrade();
        $this->trackQueries();

        $setting = Setting::byKey($key, true);

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

        $this->expectException(\InvalidArgumentException::class);
        $setting = Setting::byKey($key, true);

        $this->assertNull($setting);
    }
}
