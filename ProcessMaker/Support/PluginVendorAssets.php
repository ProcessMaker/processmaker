<?php

declare(strict_types=1);

namespace ProcessMaker\Support;

use InvalidArgumentException;

/**
 * Resolves tenant-scoped vs global public paths for plugin vendor assets.
 * Path helpers for tenant plugin public assets; used by TenantPluginAssetPublisher and later Mix/ScreenBuilder.
 */
class PluginVendorAssets
{
    private const string VENDOR_PACKAGES_PREFIX = 'vendor/processmaker/packages';

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

    /**
     * Web package folder name (e.g. package-plg) from a plugin directory.
     */
    public static function packageNameFromPluginPath(string $pluginPath, ?string $folderName = null): string
    {
        $folderName = $folderName ?? basename(rtrim($pluginPath, '/\\'));
        $composerPath = rtrim($pluginPath, '/\\') . '/composer.json';

        if (!is_file($composerPath)) {
            return $folderName;
        }

        $content = file_get_contents($composerPath);
        $data = json_decode($content, true);
        if (!is_array($data)) {
            return $folderName;
        }

        $name = $data['name'] ?? null;
        if (is_string($name) && str_contains($name, '/')) {
            return substr($name, strrpos($name, '/') + 1);
        }

        return $folderName;
    }

    public static function normalizePackageName(string $packageName): string
    {
        return trim(str_replace('\\', '/', $packageName), '/');
    }

    public static function globalVendorRelativePath(string $packageName): string
    {
        return self::VENDOR_PACKAGES_PREFIX . '/' . self::normalizePackageName($packageName);
    }

    public static function tenantStorageSegment(int $tenantId): string
    {
        return 'storage/tenant_' . $tenantId;
    }

    public static function tenantVendorRelativePath(int $tenantId, string $packageName): string
    {
        return self::tenantStorageSegment($tenantId) . '/' . self::globalVendorRelativePath($packageName);
    }

    /**
     * Absolute filesystem path to published plugin public assets for a tenant.
     */
    public static function tenantPublicRoot(int $tenantId, string $packageName): string
    {
        return public_path(self::tenantVendorRelativePath($tenantId, $packageName));
    }

    public static function globalPublicRoot(string $packageName): string
    {
        return public_path(self::globalVendorRelativePath($packageName));
    }

    /**
     * Path relative to public_path() where the tenant copy of mix-manifest.json lives.
     */
    public static function manifestRelativeDirectory(int $tenantId, string $packageName): string
    {
        return self::tenantVendorRelativePath($tenantId, $packageName);
    }

    public static function mixManifestFilesystemPath(int $tenantId, string $packageName): string
    {
        return self::tenantPublicRoot($tenantId, $packageName) . DIRECTORY_SEPARATOR . 'mix-manifest.json';
    }

    /**
     * Second argument to mix(), e.g. vendor/processmaker/packages/package-plg (no leading slash).
     */
    public static function mixManifestDirectoryArgument(string $packageName): string
    {
        return self::globalVendorRelativePath($packageName);
    }

    public static function isPluginVendorManifestDirectory(string $manifestDirectory): bool
    {
        return self::parsePackageFromManifestDirectory($manifestDirectory) !== null;
    }

    public static function parsePackageFromManifestDirectory(string $manifestDirectory): ?string
    {
        $manifestDirectory = trim(str_replace('\\', '/', $manifestDirectory), '/');
        $prefix = self::VENDOR_PACKAGES_PREFIX . '/';

        if (!str_starts_with($manifestDirectory, $prefix)) {
            return null;
        }

        $rest = substr($manifestDirectory, strlen($prefix));
        $package = explode('/', $rest, 2)[0] ?? '';

        return $package !== '' ? $package : null;
    }

    /**
     * URL path prefix for browser requests (/storage/tenant_X/vendor/...).
     */
    public static function webPrefix(int $tenantId, string $packageName): string
    {
        return '/' . self::tenantVendorRelativePath($tenantId, $packageName);
    }

    /**
     * Build a public URL path for an entry from mix-manifest (value side, usually /js/file.js?id=...).
     */
    public static function webPath(int $tenantId, string $packageName, string $manifestEntry): string
    {
        $entry = '/' . ltrim(str_replace('\\', '/', $manifestEntry), '/');

        return self::webPrefix($tenantId, $packageName) . $entry;
    }

    /**
     * Global (landlord / legacy) web path for the same manifest entry.
     */
    public static function globalWebPath(string $packageName, string $manifestEntry): string
    {
        $entry = '/' . ltrim(str_replace('\\', '/', $manifestEntry), '/');

        return '/' . self::globalVendorRelativePath($packageName) . $entry;
    }

    public static function pluginSourcePublicPath(string $pluginPath): string
    {
        return rtrim($pluginPath, '/\\') . '/public';
    }

    /**
     * Whether mix() can resolve assets for a vendor package directory (global publish or tenant copy).
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
        if ($packageName === null) {
            return false;
        }

        $tenantId = self::currentTenantId();
        if ($tenantId === null) {
            return false;
        }

        return is_file(self::mixManifestFilesystemPath($tenantId, $packageName));
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
}
