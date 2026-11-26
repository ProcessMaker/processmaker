<?php

namespace ProcessMaker\Support;

use Illuminate\Foundation\Mix;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class MultitenantMix extends Mix
{
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

        $manifest = $this->hasCurrentTenant() ? $this->getCachedMixManifest() : null;
        if ($manifest === null) {
            return parent::__invoke($path, $manifestDirectory);
        }

        $path = $this->matchTenantMixPath($path, $manifest) ?? $path;

        return parent::__invoke($path, $manifestDirectory);
    }

    private function hasCurrentTenant(): bool
    {
        return app()->bound('currentTenant') && app('currentTenant') !== null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getCachedMixManifest(): ?array
    {
        $manifest = Cache::get('mix-manifest');
        if ($manifest !== null) {
            return is_array($manifest) ? $manifest : null;
        }

        $manifestPath = public_path('mix-manifest.json');
        if (!file_exists($manifestPath)) {
            return null;
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);
        if (is_array($manifest)) {
            Cache::put('mix-manifest', $manifest, now()->addHours(24));
        }

        return is_array($manifest) ? $manifest : null;
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    private function matchTenantMixPath(string $path, array $manifest): ?string
    {
        $tenant = app('currentTenant');
        $pathInfo = pathinfo($path);
        $tenantPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_tenant_' . $tenant->id;

        $matchingKeys = array_values(array_filter(
            array_keys($manifest),
            static fn (string $key): bool => Str::contains($key, $tenantPath),
        ));

        return count($matchingKeys) === 1 ? $matchingKeys[0] : null;
    }

    /**
     * URL for a plugin package copied to the current tenant's public disk.
     * Returns null when there is no tenant copy, so the default resolution runs.
     */
    private function resolveTenantPluginVendorUrl(string $path, string $manifestDirectory): ?HtmlString
    {
        $packageName = PluginVendorAssets::parsePackageFromManifestDirectory($manifestDirectory);
        $tenantId = PluginVendorAssets::currentTenantId();
        if ($packageName === null || !PluginVendorAssets::isEnabled() || $tenantId === null) {
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
        $asset = $manifest[$key] ?? null;

        return $asset === null
            ? null
            : new HtmlString(
                (string) config('app.mix_url') . PluginVendorAssets::webPath($packageName, (string) $asset)
            );
    }
}