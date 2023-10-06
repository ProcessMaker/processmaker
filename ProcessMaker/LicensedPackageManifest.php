<?php

namespace ProcessMaker;

use Doctrine\Common\Cache\Cache;
use Illuminate\Foundation\PackageManifest;

class LicensedPackageManifest extends PackageManifest
{
    protected function packagesToIgnore()
    {
        $packagesToIgnore = Cache::get('packages_to_ignore', function () {
            $composer = json_decode(file_get_contents(base_path('composer.json')), true);

            $allPackages = collect($composer['extra']['processmaker']['enterprise'])
                ->map(fn ($k, $v) => "processmaker/{$v}")
                ->values();

            return $allPackages->intersect($this->licensedPackages());
        });

        return [...parent::packagesToIgnore(), ...$packagesToIgnore];
    }

    public function list()
    {
        return array_keys($this->getManifest());
    }

    private function licensedPackages()
    {
    }
}
