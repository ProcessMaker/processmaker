<?php

namespace ProcessMaker;

use Illuminate\Foundation\PackageManifest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class LicensedPackageManifest extends PackageManifest
{
    const EXPIRE_CACHE_KEY = 'license_expires_at';

    protected function packagesToIgnore()
    {
        $packagesToIgnore = $this->loadPackagesToIgnore()->all();
        \Log::info('License ignoring packages:', $packagesToIgnore);

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
        $default = collect(['packages']); // always allow the packages package
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

        return collect($composer['extra']['processmaker']['enterprise'])
            ->map(fn ($k, $v) => "processmaker/{$v}")
            ->values();
    }
}
