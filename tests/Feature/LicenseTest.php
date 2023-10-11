<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\PackageManifest;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\LicensedPackageManifest;
use ProcessMaker\Providers\LicenseServiceProvider;
use Tests\TestCase;

class LicenseTest extends TestCase
{
    public function testLicense()
    {
        Storage::fake('local');

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
        Storage::fake('local');

        $packageManifest = $this->app->make(PackageManifest::class);
        $packagesToIgnore = $packageManifest->loadPackagesToIgnore();

        // Without a license, all packages are enabled
        $this->assertEmpty($packagesToIgnore);
    }

    public function testExpiredLicense()
    {
        Storage::fake('local');

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
    }

    public function testProviderWithLicense()
    {
        Storage::fake('local');

        $license = [
            'expires_at' => Carbon::now()->addDays(30)->toIso8601String(),
            'packages' => [
                'package-projects',
            ],
        ];
        Storage::disk('local')->put('license.json', json_encode($license));

        Artisan::call('package:discover');

        $this->assertEquals(Cache::get(LicensedPackageManifest::EXPIRE_CACHE_KEY), Carbon::now()->addDays(30)->timestamp);

        $provider = $this->app->make(LicenseServiceProvider::class, [
            'app' => $this->app,
        ]);

        Artisan::shouldReceive('call')->never();
        $provider->boot();
    }

    public function testProviderWithExpiredLicense()
    {
        Storage::fake('local');

        $license = [
            'expires_at' => Carbon::now()->addDays(30)->toIso8601String(),
            'packages' => [
                'package-projects',
            ],
        ];
        Storage::disk('local')->put('license.json', json_encode($license));

        Artisan::call('package:discover');

        $this->assertEquals(Cache::get(LicensedPackageManifest::EXPIRE_CACHE_KEY), Carbon::now()->addDays(30)->timestamp);

        $provider = $this->app->make(LicenseServiceProvider::class, [
            'app' => $this->app,
        ]);

        $this->travel(31)->days();
        $this->assertTrue(Cache::has(LicensedPackageManifest::EXPIRE_CACHE_KEY));
        Artisan::shouldReceive('call')->once()->with('package:discover');
        $provider->boot();
    }
}
