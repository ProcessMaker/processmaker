<?php

namespace ProcessMaker\Helpers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Multitenancy\Models\Tenant;

class TenantLicense
{
    public static function hasFeature(string $feature): bool
    {
        $tenant = Tenant::current();
        Log::info('tenant', ['tenant' => $tenant]);
        if (!$tenant) {
            Log::warning('Tenant context not set when checking feature: ' . $feature);

            return false;
        }
        $path = '/license.json';
        if (!Storage::disk('local')->exists($path)) {
            return false;
        }
        $license = json_decode(Storage::disk('local')->get($path), true);

        return in_array($feature, $license['packages'] ?? []);
    }
}
