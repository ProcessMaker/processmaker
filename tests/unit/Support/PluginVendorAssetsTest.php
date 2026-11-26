<?php

namespace Tests\Unit\Support;

use ProcessMaker\Support\PluginVendorAssets;
use Tests\TestCase;

class PluginVendorAssetsTest extends TestCase
{
    private const TENANT_ID = 987651;

    public function testPathsUrlsAndAvailabilityOnTenantPublicDisk(): void
    {
        $tenantDir = base_path('storage/tenant_' . self::TENANT_ID);
        $this->deleteTree($tenantDir);

        $this->assertSame(
            base_path('storage/tenant_987651/app/public/vendor/processmaker/packages/package-plg'),
            PluginVendorAssets::tenantPackageRoot(self::TENANT_ID, 'package-plg')
        );
        $this->assertSame(
            '/storage/vendor/processmaker/packages/package-plg/js/a.js?id=1',
            PluginVendorAssets::webPath('package-plg', '/js/a.js?id=1')
        );
        $this->assertSame('package-plg', PluginVendorAssets::parsePackageFromManifestDirectory('/vendor/processmaker/packages/package-plg/'));
        $this->assertNull(PluginVendorAssets::parsePackageFromManifestDirectory('vendor/other'));

        config(['app.multitenancy' => true, 'plugin_manager.tenant_plugin_assets_enabled' => true]);
        $this->app->instance('currentTenant', (object) ['id' => self::TENANT_ID]);

        $this->assertSame(
            base_path('storage/tenant_987651/app/public/vendor/processmaker/packages/package-plg/js/a.js'),
            PluginVendorAssets::filesystemPathForStorageUrl('/storage/vendor/processmaker/packages/package-plg/js/a.js?id=1')
        );
        $this->assertNull(PluginVendorAssets::filesystemPathForStorageUrl('/storage/other/file.js'));

        $this->assertFalse(PluginVendorAssets::publishedVendorPackageIsAvailable('vendor/processmaker/packages/avail-' . self::TENANT_ID));

        $root = PluginVendorAssets::tenantPackageRoot(self::TENANT_ID, 'avail-pkg');
        mkdir($root, 0755, true);
        file_put_contents($root . '/mix-manifest.json', '{}');
        $this->assertTrue(PluginVendorAssets::publishedVendorPackageIsAvailable('vendor/processmaker/packages/avail-pkg'));

        config(['plugin_manager.tenant_plugin_assets_enabled' => false]);
        $this->assertFalse(PluginVendorAssets::isEnabled());
        $this->assertFalse(PluginVendorAssets::publishedVendorPackageIsAvailable('vendor/processmaker/packages/avail-pkg'));

        $this->app->offsetUnset('currentTenant');
        $this->deleteTree($tenantDir);
    }

    private function deleteTree(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            is_dir($path) ? $this->deleteTree($path) : unlink($path);
        }
        rmdir($dir);
    }
}
