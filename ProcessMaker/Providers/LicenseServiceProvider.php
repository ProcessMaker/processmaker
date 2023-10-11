<?php

namespace ProcessMaker\Providers;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\PackageManifest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use ProcessMaker\LicensedPackageManifest;

/**
 * Provide our ProcessMaker specific services.
 */
class LicenseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $expires = Cache::get(LicensedPackageManifest::EXPIRE_CACHE_KEY);

        if ($expires && $expires < Carbon::now()->timestamp) {
            Artisan::call('package:discover');
        }
    }

    public function register(): void
    {
        $this->app->singleton(PackageManifest::class, fn () => new LicensedPackageManifest(
            new Filesystem, $this->app->basePath(), $this->app->getCachedPackagesPath()
        ));
    }
}
