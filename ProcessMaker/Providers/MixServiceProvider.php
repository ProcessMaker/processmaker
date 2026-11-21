<?php

namespace ProcessMaker\Providers;

use Illuminate\Foundation\Mix;
use Illuminate\Foundation\MixFileNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\HtmlString;
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
                /** @var array<string, array<string, mixed>|null> */
                private static array $tenantPluginManifests = [];

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
                    if (!str_starts_with($path, '/')) {
                        $path = '/' . $path;
                    }

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
                    }

                    return parent::__invoke($path, $manifestDirectory);
                }

                /**
                 * @return HtmlString|null Tenant plugin asset URL, or null to fall back to default mix().
                 */
                private function resolveTenantPluginVendorUrl(string $path, string $manifestDirectory): ?HtmlString
                {
                    if ($manifestDirectory === '') {
                        return null;
                    }

                    if (!PluginVendorAssets::isEnabled()) {
                        return null;
                    }

                    $packageName = PluginVendorAssets::parsePackageFromManifestDirectory(
                        trim(str_replace('\\', '/', $manifestDirectory), '/')
                    );
                    if ($packageName === null) {
                        return null;
                    }

                    $tenantId = PluginVendorAssets::currentTenantId();
                    if ($tenantId === null) {
                        return null;
                    }

                    $relativeDirectory = PluginVendorAssets::manifestRelativeDirectory($tenantId, $packageName);
                    $manifestFilesystemPath = PluginVendorAssets::mixManifestFilesystemPath($tenantId, $packageName);

                    if (!is_file($manifestFilesystemPath)) {
                        return null;
                    }

                    if (is_file(public_path($relativeDirectory . '/hot'))) {
                        return $this->resolveHotUrl($path, public_path($relativeDirectory . '/hot'));
                    }

                    if (!isset(self::$tenantPluginManifests[$manifestFilesystemPath])) {
                        self::$tenantPluginManifests[$manifestFilesystemPath] = json_decode(
                            file_get_contents($manifestFilesystemPath),
                            true
                        );
                    }

                    $manifest = self::$tenantPluginManifests[$manifestFilesystemPath];
                    if (!is_array($manifest) || !isset($manifest[$path])) {
                        if (!app('config')->get('app.debug')) {
                            report(new MixFileNotFoundException("Unable to locate Mix file: {$path}."));

                            return new HtmlString($path);
                        }

                        throw new MixFileNotFoundException("Unable to locate Mix file: {$path}.");
                    }

                    $url = app('config')->get('app.mix_url')
                        . PluginVendorAssets::webPath($tenantId, $packageName, (string) $manifest[$path]);

                    return new HtmlString($url);
                }

                private function resolveHotUrl(string $path, string $hotFilePath): HtmlString
                {
                    $url = rtrim(file_get_contents($hotFilePath));

                    $customUrl = app('config')->get('app.mix_hot_proxy_url');
                    if (!empty($customUrl)) {
                        return new HtmlString("{$customUrl}{$path}");
                    }

                    if (Str::startsWith($url, ['http://', 'https://'])) {
                        return new HtmlString(Str::after($url, ':') . $path);
                    }

                    return new HtmlString('//localhost:8080' . $path);
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
