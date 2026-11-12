<?php

namespace Tests\Unit\Managers;

use ProcessMaker\Managers\PluginManager;
use ProcessMaker\Support\PluginVendorAssets;
use ReflectionMethod;
use Tests\TestCase;

class PluginManagerTenantAssetsTest extends TestCase
{
    public function testTenantAssetHooksPublishAndRemoveWhenMultitenancyEnabled(): void
    {
        config(['app.multitenancy' => true]);
        config(['plugin_manager.tenant_plugin_assets_enabled' => true]);
        $this->app->instance('currentTenant', (object) ['id' => 6]);

        $pluginDir = storage_path('plugins/pm-tenant-assets-' . uniqid());
        mkdir($pluginDir, 0755, true);
        file_put_contents($pluginDir . '/composer.json', json_encode([
            'name' => 'processmaker/tenant-assets-plugin',
        ]));
        mkdir($pluginDir . '/public/js', 0755, true);
        file_put_contents($pluginDir . '/public/js/app.js', '// published by install hook');

        $manager = new PluginManager();
        $publish = new ReflectionMethod($manager, 'publishTenantPluginAssetsIfApplicable');
        $publish->invoke($manager, $pluginDir, 'tenant-assets-plugin', null);

        $published = PluginVendorAssets::tenantPublicRoot(6, 'tenant-assets-plugin') . '/js/app.js';
        $this->assertFileExists($published);

        $remove = new ReflectionMethod($manager, 'removeTenantPluginAssetsIfApplicable');
        $remove->invoke($manager, $pluginDir, 'tenant-assets-plugin', null);
        $this->assertFileDoesNotExist($published);

        config(['app.multitenancy' => false]);
        $offDir = storage_path('plugins/pm-tenant-assets-off-' . uniqid());
        mkdir($offDir . '/public/js', 0755, true);
        file_put_contents($offDir . '/composer.json', json_encode([
            'name' => 'processmaker/off-tenant-plugin',
        ]));
        file_put_contents($offDir . '/public/js/app.js', '// noop');
        $publish->invoke($manager, $offDir, 'off-tenant-plugin', null);
        $this->assertFileDoesNotExist(
            PluginVendorAssets::tenantPublicRoot(6, 'off-tenant-plugin') . '/js/app.js'
        );

        $this->deleteDirectory($pluginDir);
        $this->deleteDirectory($offDir);
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
