<?php

declare(strict_types=1);

namespace ProcessMaker\Managers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Support\PluginVendorAssets;
use Throwable;

/**
 * Copies a plugin's public/ tree onto the tenant public disk:
 * storage/tenant_{id}/app/public/vendor/processmaker/packages/{package}/
 *
 * This is the root Storage::disk('public') uses while that tenant is current, so
 * storage/{path} serves the files at /storage/vendor/processmaker/packages/{package}/...
 * The path is built from the tenant id, so it does not depend on Laravel's disk cache.
 */
class TenantPluginAssetPublisher
{
    /**
     * @return bool True when assets were copied; false when the plugin has no public directory.
     */
    public function publishPlugin(string $pluginPath, ?int $tenantId = null): bool
    {
        $tenantId = PluginVendorAssets::requireTenantId($tenantId);
        $source = PluginVendorAssets::pluginSourcePublicPath($pluginPath);

        if (!is_dir($source)) {
            Log::info('Tenant plugin assets: skipping plugin with no public directory', [
                'tenant_id' => $tenantId,
                'plugin_path' => $pluginPath,
            ]);

            return false;
        }

        $packageName = PluginVendorAssets::packageNameFromPluginPath($pluginPath);
        $destination = PluginVendorAssets::tenantPackageRoot($tenantId, $packageName);

        if (File::isDirectory($destination)) {
            File::deleteDirectory($destination);
        }
        File::ensureDirectoryExists(dirname($destination));
        File::copyDirectory($source, $destination);

        Log::info('Tenant plugin assets published', [
            'tenant_id' => $tenantId,
            'package' => $packageName,
            'source' => $source,
            'destination' => $destination,
        ]);

        return true;
    }

    /**
     * @return array<string, bool> plugin folder name => published (true) or skipped/failed (false)
     */
    public function publishAllInstalledPlugins(?int $tenantId = null): array
    {
        $tenantId = PluginVendorAssets::requireTenantId($tenantId);
        $results = [];
        $pluginsFolder = storage_path('plugins');

        if (!is_dir($pluginsFolder)) {
            return $results;
        }

        foreach (glob($pluginsFolder . '/*', GLOB_ONLYDIR) ?: [] as $pluginPath) {
            $folderName = basename($pluginPath);
            if (str_starts_with($folderName, '_')) {
                continue;
            }

            try {
                $results[$folderName] = $this->publishPlugin($pluginPath, $tenantId);
            } catch (Throwable $exception) {
                Log::error('Tenant plugin assets: failed to publish plugin', [
                    'tenant_id' => $tenantId,
                    'plugin_path' => $pluginPath,
                    'message' => $exception->getMessage(),
                ]);
                $results[$folderName] = false;
            }
        }

        return $results;
    }

    public function removePublishedPlugin(string $pluginPath, ?int $tenantId = null): void
    {
        $tenantId = PluginVendorAssets::requireTenantId($tenantId);
        $packageName = PluginVendorAssets::packageNameFromPluginPath($pluginPath);
        $destination = PluginVendorAssets::tenantPackageRoot($tenantId, $packageName);

        if (!File::isDirectory($destination)) {
            return;
        }

        File::deleteDirectory($destination);

        Log::info('Tenant plugin assets removed', [
            'tenant_id' => $tenantId,
            'package' => $packageName,
            'destination' => $destination,
        ]);
    }
}
