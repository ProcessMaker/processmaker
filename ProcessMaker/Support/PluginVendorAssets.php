<?php

declare(strict_types=1);

namespace ProcessMaker\Support;

use InvalidArgumentException;

/**
 * Paths and URLs for plugin vendor assets published on each tenant's public disk.
 *
 * Tenant copy:  storage/tenant_{id}/app/public/vendor/processmaker/packages/{package}/
 * Browser URL:  /storage/vendor/processmaker/packages/{package}/...  (served by storage/{path})
 *
 * The tenant id is not part of the URL: SwitchTenant points the public disk at the
 * current tenant, so the same URL resolves to a different file per tenant.
 */
class PluginVendorAssets
{
    public const VENDOR_PACKAGES_PREFIX = 'vendor/processmaker/packages';

    public static function isEnabled(): bool
    {
        if (!config('app.multitenancy')) {
            return false;
        }

        if (!filter_var(config('plugin_manager.tenant_plugin_assets_enabled', true), FILTER_VALIDATE_BOOL)) {
            return false;
        }

        return self::currentTenantId() !== null;
    }

    public static function currentTenantId(): ?int
    {
        if (!app()->bound('currentTenant')) {
            return null;
        }

        $tenant = app('currentTenant');
        if (!is_object($tenant) || !isset($tenant->id)) {
            return null;
        }

        return (int) $tenant->id;
    }

    public static function requireTenantId(?int $tenantId): int
    {
        if ($tenantId !== null) {
            return $tenantId;
        }

        $current = self::currentTenantId();
        if ($current === null) {
            throw new InvalidArgumentException('No current tenant in application context.');
        }

        return $current;
    }

    /**
     * Package folder name (e.g. package-plg) from the plugin composer.json name, else the folder name.
     */
    public static function packageNameFromPluginPath(string $pluginPath): string
    {
        $pluginPath = rtrim($pluginPath, '/\\');
        $folderName = basename($pluginPath);
        $composerPath = $pluginPath . '/composer.json';

        if (!is_file($composerPath)) {
            return $folderName;
        }

        $data = json_decode((string) file_get_contents($composerPath), true);
        $name = is_array($data) ? ($data['name'] ?? null) : null;

        if (is_string($name) && str_contains($name, '/')) {
            return substr($name, strrpos($name, '/') + 1);
        }

        return $folderName;
    }

    public static function pluginSourcePublicPath(string $pluginPath): string
    {
        return rtrim($pluginPath, '/\\') . '/public';
    }

    /**
     * Path relative to a public disk root, e.g. vendor/processmaker/packages/package-plg.
     */
    public static function relativeVendorPath(string $packageName): string
    {
        return self::VENDOR_PACKAGES_PREFIX . '/' . trim(str_replace('\\', '/', $packageName), '/');
    }

    /**
     * Root of the tenant public disk: storage/tenant_{id}/app/public.
     * Same root Storage::disk('public') uses while that tenant is current.
     */
    public static function tenantPublicDiskRoot(int $tenantId): string
    {
        return base_path('storage/tenant_' . $tenantId . '/app/public');
    }

    public static function tenantPackageRoot(int $tenantId, string $packageName): string
    {
        return self::tenantPublicDiskRoot($tenantId) . '/' . self::relativeVendorPath($packageName);
    }

    public static function mixManifestPath(int $tenantId, string $packageName): string
    {
        return self::tenantPackageRoot($tenantId, $packageName) . '/mix-manifest.json';
    }

    /**
     * URL prefix served by storage/{path}, without the tenant id.
     */
    public static function webPrefix(string $packageName): string
    {
        return '/storage/' . self::relativeVendorPath($packageName);
    }

    /**
     * Public URL for a mix-manifest value such as /js/file.js?id=hash.
     */
    public static function webPath(string $packageName, string $manifestEntry): string
    {
        return self::webPrefix($packageName) . '/' . ltrim(str_replace('\\', '/', $manifestEntry), '/');
    }

    /**
     * Package name from a mix() manifest directory such as vendor/processmaker/packages/package-plg.
     */
    public static function parsePackageFromManifestDirectory(string $manifestDirectory): ?string
    {
        $manifestDirectory = trim(str_replace('\\', '/', $manifestDirectory), '/');
        $prefix = self::VENDOR_PACKAGES_PREFIX . '/';

        if (!str_starts_with($manifestDirectory, $prefix)) {
            return null;
        }

        $package = explode('/', substr($manifestDirectory, strlen($prefix)), 2)[0] ?? '';

        return $package !== '' ? $package : null;
    }

    /**
     * Whether a vendor package directory has assets for this request:
     * the global public/vendor copy, or the current tenant's copy on its public disk.
     */
    public static function publishedVendorPackageIsAvailable(string $manifestDirectory): bool
    {
        $manifestDirectory = trim(str_replace('\\', '/', $manifestDirectory), '/');
        if ($manifestDirectory === '') {
            return false;
        }

        if (is_dir(public_path($manifestDirectory))) {
            return true;
        }

        if (!self::isEnabled()) {
            return false;
        }

        $packageName = self::parsePackageFromManifestDirectory($manifestDirectory);
        $tenantId = self::currentTenantId();
        if ($packageName === null || $tenantId === null) {
            return false;
        }

        return is_file(self::mixManifestPath($tenantId, $packageName));
    }

    /**
     * Map a /storage/vendor/processmaker/packages/... URL to a file on the current tenant public disk.
     */
    public static function filesystemPathForStorageUrl(string $webPath): ?string
    {
        $path = explode('?', str_replace('\\', '/', $webPath), 2)[0];
        $prefix = '/storage/' . self::VENDOR_PACKAGES_PREFIX . '/';

        if (!str_starts_with($path, $prefix) || str_contains($path, '..')) {
            return null;
        }

        $tenantId = self::currentTenantId();
        if ($tenantId === null) {
            return null;
        }

        return self::tenantPublicDiskRoot($tenantId) . '/' . substr($path, strlen('/storage/'));
    }
}
