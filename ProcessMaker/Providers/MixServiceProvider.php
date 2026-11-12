<?php

namespace ProcessMaker\Providers;

use Illuminate\Foundation\Mix;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use ProcessMaker\Support\PluginVendorAssets;

class MixServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(Mix::class, function ($app) {
            return new class extends Mix {
                /** @var array<string, array<string, mixed>> */
                private static array $tenantManifests = [];

                /**
                 * Get the path to a versioned Mix file.
                 *
                 * @param  string  $path
                 * @param  string  $manifestDirectory
                 * @return \Illuminate\Support\HtmlString|string
                 *
                 * @throws \Exception
                 */
                public function __invoke($path, $manifestDirectory = '')
                {
                    $tenantPluginUrl = $this->resolveTenantPluginVendorUrl($path, $manifestDirectory);
                    if ($tenantPluginUrl !== null) {
                        return $tenantPluginUrl;
                    }

                    $tenant = app()->bound('currentTenant') ? app('currentTenant') : null;

                    if (!$tenant) {
                        return parent::__invoke($path, $manifestDirectory);
                    }

                    // Try to get manifest from cache first
                    $manifest = Cache::get('mix-manifest');

                    // If not in cache, read from file and cache it
                    if (is_null($manifest)) {
                        $manifestPath = public_path('mix-manifest.json');
                        if (!file_exists($manifestPath)) {
                            return parent::__invoke($path, $manifestDirectory);
                        }

                        $manifest = json_decode(file_get_contents($manifestPath), true);
                        if ($manifest !== null) {
                            Cache::put('mix-manifest', $manifest, now()->addHours(24));
                        }
                    }

                    if ($manifest === null) {
                        return parent::__invoke($path, $manifestDirectory);
                    }

                    $keys = array_keys($manifest);

                    $pathInfo = pathinfo($path);
                    $tenantPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_tenant_' . $tenant->id;

                    $matchingKeys = array_values(array_filter($keys, function ($key) use ($tenantPath) {
                        return Str::contains($key, $tenantPath);
                    }));

                    if (count($matchingKeys) === 1) {
                        $path = $matchingKeys[0];
                        // \Log::info('MixServiceProvider: ' . $path);
                    }

                    return parent::__invoke($path, $manifestDirectory);
                }

                /**
                 * URL for a plugin package copied to the current tenant's public disk.
                 * Returns null when there is no tenant copy, so the default resolution runs.
                 */
                private function resolveTenantPluginVendorUrl(string $path, string $manifestDirectory): ?HtmlString
                {
                    $packageName = PluginVendorAssets::parsePackageFromManifestDirectory($manifestDirectory);
                    if ($packageName === null || !PluginVendorAssets::isEnabled()) {
                        return null;
                    }

                    $tenantId = PluginVendorAssets::currentTenantId();
                    if ($tenantId === null) {
                        return null;
                    }

                    $manifestPath = PluginVendorAssets::mixManifestPath($tenantId, $packageName);
                    if (!is_file($manifestPath)) {
                        return null;
                    }

                    if (!isset(self::$tenantManifests[$manifestPath])) {
                        $decoded = json_decode((string) file_get_contents($manifestPath), true);
                        self::$tenantManifests[$manifestPath] = is_array($decoded) ? $decoded : [];
                    }

                    $key = '/' . ltrim(str_replace('\\', '/', $path), '/');
                    $manifest = self::$tenantManifests[$manifestPath];
                    if (!isset($manifest[$key])) {
                        return null;
                    }

                    return new HtmlString(
                        (string) config('app.mix_url') . PluginVendorAssets::webPath($packageName, (string) $manifest[$key])
                    );
                }
            };
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
