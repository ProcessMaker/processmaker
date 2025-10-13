<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\PackageManifest;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Mockery;
use ProcessMaker\LicensedPackageManifest;
use ProcessMaker\Providers\LicenseServiceProvider;
use Tests\TestCase;

/**
 * Test license commands
 *
 * @group license
 */
class LicenseTest extends TestCase
{
    protected $skipPackageDiscover = false;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        // remove the license.json file if it exists
        if (Storage::disk('local')->exists('license.json')) {
            Storage::disk('local')->delete('license.json');
        }
        Cache::forget(LicensedPackageManifest::EXPIRE_CACHE_KEY);

        if (!$this->skipPackageDiscover) {
            Mockery::close(); // Clear the mock
            $this->artisan('package:discover');
        }

        // Restore mock of artisan
        parent::tearDown();
    }

    public function testLicense()
    {
        $license = [
            'expires_at' => Carbon::now()->addDays(30)->toIso8601String(),
            'packages' => [
                'package-translations',
                'package-projects',
            ],
        ];

        Storage::disk('local')->put('license.json', json_encode($license));

        $packageManifest = $this->app->make(PackageManifest::class);
        $packagesToIgnore = $packageManifest->loadPackagesToIgnore();

        $this->assertTrue($packagesToIgnore->contains('processmaker/package-vocabularies'));
        $this->assertFalse($packagesToIgnore->contains('processmaker/package-translations'));
        $this->assertFalse($packagesToIgnore->contains('processmaker/package-projects'));
        $this->assertFalse($packagesToIgnore->contains('processmaker/packages'));
    }

    public function testNoLicense()
    {
        $packageManifest = $this->app->make(PackageManifest::class);
        $packagesToIgnore = $packageManifest->loadPackagesToIgnore();

        // Without a license, all packages are enabled
        $this->assertEmpty($packagesToIgnore);
    }

    public function testExpiredLicense()
    {
        $license = [
            'expires_at' => Carbon::now()->addDays(30)->toIso8601String(),
            'packages' => [
                'package-translations',
                'package-projects',
            ],
        ];

        Storage::disk('local')->put('license.json', json_encode($license));

        Carbon::setTestNow(Carbon::now()->addDays(31));

        $packageManifest = $this->app->make(PackageManifest::class);
        $packagesToIgnore = $packageManifest->loadPackagesToIgnore();

        $this->assertTrue($packagesToIgnore->contains('processmaker/package-vocabularies'));
        $this->assertTrue($packagesToIgnore->contains('processmaker/package-translations'));
        $this->assertTrue($packagesToIgnore->contains('processmaker/package-projects'));
    }

    public function testProviderNoLicense()
    {
        $provider = $this->app->make(LicenseServiceProvider::class, [
            'app' => $this->app,
        ]);

        Cache::forget(LicensedPackageManifest::EXPIRE_CACHE_KEY);
        Artisan::shouldReceive('call')->never();
        $provider->boot();

        $this->skipPackageDiscover = true;
    }

    public function testProviderWithLicense()
    {
        $date = Carbon::now()->addDays(30);
        $license = [
            'expires_at' => $date->toIso8601String(),
            'packages' => [
                'package-projects',
            ],
        ];
        Storage::disk('local')->put('license.json', json_encode($license));

        Artisan::call('package:discover');

        $this->assertEquals(Cache::get(LicensedPackageManifest::EXPIRE_CACHE_KEY), $date->timestamp);

        $provider = $this->app->make(LicenseServiceProvider::class, [
            'app' => $this->app,
        ]);

        Artisan::shouldReceive('call')->never();
        $provider->boot();
    }

    public function testProviderWithExpiredLicense()
    {
        $date = Carbon::now()->addDays(30);
        $license = [
            'expires_at' => $date->toIso8601String(),
            'packages' => [
                'package-projects',
            ],
        ];
        Storage::disk('local')->put('license.json', json_encode($license));

        Artisan::call('package:discover');

        $this->assertEquals(Cache::get(LicensedPackageManifest::EXPIRE_CACHE_KEY), $date->timestamp);

        $provider = $this->app->make(LicenseServiceProvider::class, [
            'app' => $this->app,
        ]);

        $this->travel(31)->days();
        $this->assertTrue(Cache::has(LicensedPackageManifest::EXPIRE_CACHE_KEY));

        Cache::put(LicensedPackageManifest::LAST_PACKAGE_DISCOVERY, 0);
        $provider->boot();
        $this->assertNotEmpty(Cache::get(LicensedPackageManifest::LAST_PACKAGE_DISCOVERY));
    }
}
