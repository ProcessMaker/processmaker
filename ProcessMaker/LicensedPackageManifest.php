<?php

namespace ProcessMaker;

use Illuminate\Foundation\PackageManifest;

class LicensedPackageManifest extends PackageManifest
{
    protected function packagesToIgnore()
    {
        $composer = json_decode(file_get_contents(base_path('composer.json')), true);
        $disable = collect($composer['extra']['processmaker']['enterprise'])->map(fn ($k, $v) => "processmaker/{$v}")->values();
        return [...parent::packagesToIgnore(), ...$disable];
    }

    public function list()
    {
        return array_keys($this->getManifest());
    }

}