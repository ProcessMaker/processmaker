<?php

namespace Tests\Unit\Managers;

use ProcessMaker\Managers\TenantPluginAssetPublisher;
use ProcessMaker\Support\PluginVendorAssets;
use Tests\TestCase;

class TenantPluginAssetPublisherTest extends TestCase
{
    private const TENANT_ID = 987652;

    public function testCopiesToTenantPublicDiskAndRemoves(): void
    {
        $tenantDir = base_path('storage/tenant_' . self::TENANT_ID);
        $this->deleteTree($tenantDir);

        $pluginDir = storage_path('plugins/tenant-assets-test-' . uniqid());
        mkdir($pluginDir, 0755, true);
        file_put_contents($pluginDir . '/composer.json', json_encode(['name' => 'processmaker/test-tenant-pkg']));

        $publisher = new TenantPluginAssetPublisher();
        $destination = PluginVendorAssets::tenantPackageRoot(self::TENANT_ID, 'test-tenant-pkg');

        $this->assertFalse($publisher->publishPlugin($pluginDir, self::TENANT_ID));
        $this->assertDirectoryDoesNotExist($destination);

        mkdir($pluginDir . '/public/js', 0755, true);
        file_put_contents($pluginDir . '/public/js/app.js', 'console.log("v1");');
        file_put_contents($pluginDir . '/public/mix-manifest.json', '{"\/js\/app.js":"\/js\/app.js?id=1"}');

        $this->assertTrue($publisher->publishPlugin($pluginDir, self::TENANT_ID));
        $this->assertSame('console.log("v1");', file_get_contents($destination . '/js/app.js'));
        $this->assertFileExists($destination . '/mix-manifest.json');
        $this->assertDirectoryDoesNotExist(public_path('vendor/processmaker/packages/test-tenant-pkg'));

        file_put_contents($pluginDir . '/public/js/app.js', 'console.log("v2");');
        $this->assertTrue($publisher->publishPlugin($pluginDir, self::TENANT_ID));
        $this->assertSame('console.log("v2");', file_get_contents($destination . '/js/app.js'));

        $publisher->removePublishedPlugin($pluginDir, self::TENANT_ID);
        $this->assertDirectoryDoesNotExist($destination);

        $this->deleteTree($pluginDir);
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
