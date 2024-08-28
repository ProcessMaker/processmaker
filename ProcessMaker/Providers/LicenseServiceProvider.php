<?php

namespace ProcessMaker\Providers;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\PackageManifest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use Predis\Connection\ConnectionException;
use ProcessMaker\LicensedPackageManifest;
use RedisException;

/**
 * Provide our ProcessMaker specific services.
 */
class LicenseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app['events']->listen(CommandFinished::class, function ($event) {
            if ($event->command == 'clear-compiled' || $event->command == 'optimize:clear') {
                Artisan::call('package:discover');
            }
        });

        try {
            $expires = Cache::get(LicensedPackageManifest::EXPIRE_CACHE_KEY);
        } catch (ConnectionException | RedisException $e) {
            $expires = null;
        }

        if ($expires && $expires < Carbon::now()->timestamp) {
            // Run package:discover only once per instance
            LicensedPackageManifest::discoverPackagesOnce();
        }
    }

    public function register(): void
    {
        $this->app->singleton(PackageManifest::class, fn () => new LicensedPackageManifest(
            new Filesystem, $this->app->basePath(), $this->app->getCachedPackagesPath()
        ));
    }
}
