<?php

namespace Tests\Unit\Support;

use ProcessMaker\Support\PluginVendorAssets;
use Tests\TestCase;

class PluginVendorAssetsTest extends TestCase
{
    public function testPluginVendorAssetsPathHelpersAndFlags(): void
    {
        $pluginDir = sys_get_temp_dir() . '/pm-plugin-vendor-assets-' . uniqid();
        mkdir($pluginDir, 0755, true);
        file_put_contents($pluginDir . '/composer.json', json_encode([
            'name' => 'processmaker/package-plg',
        ]));

        $this->assertSame(
            'package-plg',
            PluginVendorAssets::packageNameFromPluginPath($pluginDir, 'package-plg2')
        );

        $emptyDir = sys_get_temp_dir() . '/pm-plugin-vendor-assets-empty-' . uniqid();
        mkdir($emptyDir, 0755, true);
        $this->assertSame(
            'my-folder',
            PluginVendorAssets::packageNameFromPluginPath($emptyDir, 'my-folder')
        );

        $tenantId = 2;
        $package = 'package-plg';
        $relative = PluginVendorAssets::tenantVendorRelativePath($tenantId, $package);
        $this->assertSame('storage/tenant_2/vendor/processmaker/packages/package-plg', $relative);
        $this->assertSame(public_path($relative), PluginVendorAssets::tenantPublicRoot($tenantId, $package));
        $this->assertSame('/' . $relative, PluginVendorAssets::webPrefix($tenantId, $package));

        $this->assertSame(
            '/storage/tenant_2/vendor/processmaker/packages/package-plg/js/screen-builder-form-components.js?id=abc',
            PluginVendorAssets::webPath($tenantId, $package, '/js/screen-builder-form-components.js?id=abc')
        );

        $this->assertSame(
            '/vendor/processmaker/packages/package-plg/js/custom-components-admin.js?id=xyz',
            PluginVendorAssets::globalWebPath($package, '/js/custom-components-admin.js?id=xyz')
        );

        $this->assertSame(
            'package-plg',
            PluginVendorAssets::parsePackageFromManifestDirectory('vendor/processmaker/packages/package-plg')
        );
        $this->assertSame(
            'package-plg',
            PluginVendorAssets::parsePackageFromManifestDirectory('/vendor/processmaker/packages/package-plg/')
        );
        $this->assertNull(PluginVendorAssets::parsePackageFromManifestDirectory(''));
        $this->assertNull(PluginVendorAssets::parsePackageFromManifestDirectory('vendor/other'));
        $this->assertTrue(PluginVendorAssets::isPluginVendorManifestDirectory('vendor/processmaker/packages/package-plg'));

        config(['app.multitenancy' => false]);
        config(['plugin_manager.tenant_plugin_assets_enabled' => true]);
        $this->app->instance('currentTenant', (object) ['id' => 1]);
        $this->assertFalse(PluginVendorAssets::isEnabled());

        config(['app.multitenancy' => true]);
        $this->assertTrue(PluginVendorAssets::isEnabled());

        $this->app->offsetUnset('currentTenant');
        $this->assertFalse(PluginVendorAssets::isEnabled());

        config(['plugin_manager.tenant_plugin_assets_enabled' => false]);
        $this->app->instance('currentTenant', (object) ['id' => 3]);
        $this->assertFalse(PluginVendorAssets::isEnabled());

        config(['plugin_manager.tenant_plugin_assets_enabled' => true]);
        $this->app->instance('currentTenant', (object) ['id' => 5]);
        $tenantAvailabilityDir = PluginVendorAssets::tenantPublicRoot(5, 'avail-pkg');
        mkdir($tenantAvailabilityDir, 0755, true);
        file_put_contents($tenantAvailabilityDir . '/mix-manifest.json', '{}');
        $this->assertDirectoryDoesNotExist(public_path('vendor/processmaker/packages/avail-pkg'));
        $this->assertTrue(
            PluginVendorAssets::publishedVendorPackageIsAvailable('vendor/processmaker/packages/avail-pkg')
        );
        $this->deleteDirectory($tenantAvailabilityDir);

        $this->deleteDirectory($pluginDir);
        $this->deleteDirectory($emptyDir);
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
