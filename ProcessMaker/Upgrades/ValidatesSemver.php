<?php

namespace ProcessMaker\Upgrades;

use Illuminate\Support\Facades\Validator;

trait ValidatesSemver
{
    /**
     * Validates a given string as having proper semantic versioning syntax
     *
     * @param  string  $version
     *
     * @return bool
     */
    protected function validateSemver(string $version)
    {
        if (blank($version)) {
            return false;
        }

        $validator = Validator::make(
            ['to' => $version],
            ['to' => 'regex:/^([0-9]+)\.([0-9]+)\.([0-9]+)(?:-([0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*))?(?:\+[0-9A-Za-z-]+)?$/i']
        );

        return ! $validator->fails();
    }
}
