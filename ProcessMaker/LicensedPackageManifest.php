<?php

namespace ProcessMaker;

use Illuminate\Foundation\PackageManifest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class LicensedPackageManifest extends PackageManifest
{
    const EXPIRE_CACHE_KEY = 'license_expires_at';

    const DISCOVER_PACKAGES_LOCK_KEY = 'discover_package_lock_key';

    const DISCOVER_PACKAGES = 'package:discover';

    const LAST_PACKAGE_DISCOVERY = 0;

    protected function packagesToIgnore()
    {
        $packagesToIgnore = $this->loadPackagesToIgnore()->all();

        return [...parent::packagesToIgnore(), ...$packagesToIgnore];
    }

    public function loadPackagesToIgnore()
    {
        if (!$this->hasLicenseFile()) {
            return collect([]); // Allow all packages
        }

        $this->setExpireCache();

        $licensedPackages = $this->licensedPackages();

        return $this->allPackages()
            ->reject(fn ($package) => $licensedPackages->contains($package))
            ->values();
    }

    public function list()
    {
        return array_keys($this->getManifest());
    }

    private function parseLicense()
    {
        if (!$this->hasLicenseFile()) {
            return null;
        }
        $license = Storage::disk('local')->get('license.json');

        return json_decode($license, true);
    }

    private function licensedPackages()
    {
        $default = collect(['packages', 'package-ab-testing']); // always allow the packages package
        $data = $this->parseLicense();
        $expires = Carbon::parse($data['expires_at']);
        if ($expires->isPast()) {
            $packages = $default;
        } else {
            $packages = $default->concat($data['packages']);
        }

        return $packages->map(fn ($k) => "processmaker/{$k}");
    }

    private function hasLicenseFile()
    {
        return Storage::disk('local')->exists('license.json');
    }

    private function setExpireCache()
    {
        if ($data = $this->parseLicense()) {
            $expires = Carbon::parse($data['expires_at']);
            if ($expires->isPast()) {
                Cache::forget(self::EXPIRE_CACHE_KEY);
            } else {
                Cache::forever(self::EXPIRE_CACHE_KEY, $expires->timestamp);
            }
        } else {
            Cache::forget(self::EXPIRE_CACHE_KEY);
        }
    }

    private function allPackages()
    {
        $composer = json_decode(file_get_contents(base_path('composer.json')), true);

        // Add enterprise packages
        $packages = $composer['extra']['processmaker']['enterprise'];
        // Add custom packages
        $packages = array_merge($packages, $composer['extra']['processmaker']['custom']);
        // Add docker-executors packages
        $packages = array_merge($packages, $composer['extra']['processmaker']['docker-executors']);

        return collect($packages)
            ->map(fn ($k, $v) => "processmaker/{$v}")
            ->values();
    }

    /**
     * Discovers packages ensuring there's no overlapping or concurrent executions of package:discover.
     *
     * @param int $lockDurationSeconds The duration in minutes to consider the lock file as valid.
     * @return void
     */
    public static function discoverPackagesOnce(int $lockDurationSeconds = 60): void
    {
        $lock = Cache::lock(self::DISCOVER_PACKAGES_LOCK_KEY, $lockDurationSeconds);
        if ($lock->get()) {
            try {
                Artisan::call(self::DISCOVER_PACKAGES);
                Cache::put(self::LAST_PACKAGE_DISCOVERY, Carbon::now()->timestamp);
            } catch (Throwable $e) {
                Log::error('LicenseService - Error during package discovery: ' . $e->getMessage());
            }
            $lock->release();
        }
    }
}
