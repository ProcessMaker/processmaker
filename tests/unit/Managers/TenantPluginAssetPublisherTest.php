<?php

namespace Tests\Unit\Managers;

use ProcessMaker\Managers\TenantPluginAssetPublisher;
use ProcessMaker\Support\PluginVendorAssets;
use Tests\TestCase;

class TenantPluginAssetPublisherTest extends TestCase
{
    public function testTenantPluginAssetPublisherCopyRemoveAndSkip(): void
    {
        $tenantId = 98765;
        $pluginDir = storage_path('plugins/tenant-assets-test-' . uniqid());
        $publishedRoot = PluginVendorAssets::tenantPublicRoot($tenantId, 'test-tenant-pkg');

        mkdir($pluginDir, 0755, true);
        file_put_contents($pluginDir . '/composer.json', json_encode([
            'name' => 'processmaker/test-tenant-pkg',
        ]));

        $publisher = new TenantPluginAssetPublisher();

        $this->assertFalse($publisher->publishPlugin($pluginDir, $tenantId));
        $this->assertDirectoryDoesNotExist($publishedRoot);

        $publicDir = $pluginDir . '/public';
        mkdir($publicDir . '/js', 0755, true);
        file_put_contents($publicDir . '/js/app.js', 'console.log("tenant-pkg");');
        file_put_contents($publicDir . '/mix-manifest.json', '{"\/js/app.js":"\/js/app.js?id=1"}');

        $this->assertTrue($publisher->publishPlugin($pluginDir, $tenantId));
        $this->assertFileExists($publishedRoot . '/js/app.js');
        $this->assertFileExists($publishedRoot . '/mix-manifest.json');
        $this->assertSame(
            'console.log("tenant-pkg");',
            file_get_contents($publishedRoot . '/js/app.js')
        );

        file_put_contents($publicDir . '/js/app.js', 'console.log("updated");');
        $this->assertTrue($publisher->publishPlugin($pluginDir, $tenantId));
        $this->assertSame(
            'console.log("updated");',
            file_get_contents($publishedRoot . '/js/app.js')
        );

        $publisher->removePublishedPlugin($pluginDir, $tenantId);
        $this->assertDirectoryDoesNotExist($publishedRoot);

        $this->deleteDirectory($pluginDir);
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
