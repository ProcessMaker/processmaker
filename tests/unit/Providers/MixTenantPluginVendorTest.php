<?php

namespace Tests\Unit\Providers;

use Illuminate\Foundation\Mix;
use ProcessMaker\Support\PluginVendorAssets;
use Tests\TestCase;

class MixTenantPluginVendorTest extends TestCase
{
    private const TENANT_ID = 987653;

    public function testMixUsesTenantPublicDiskUrlWithoutTenantIdInPath(): void
    {
        $tenantDir = base_path('storage/tenant_' . self::TENANT_ID);
        $this->deleteTree($tenantDir);

        config(['app.multitenancy' => true, 'plugin_manager.tenant_plugin_assets_enabled' => true]);
        $this->app->instance('currentTenant', (object) ['id' => self::TENANT_ID]);

        $root = PluginVendorAssets::tenantPackageRoot(self::TENANT_ID, 'mix-test-pkg');
        mkdir($root . '/js', 0755, true);
        file_put_contents($root . '/mix-manifest.json', json_encode([
            '/js/widget.js' => '/js/widget.js?id=tenant-hash',
        ]));

        $url = (string) app(Mix::class)('js/widget.js', 'vendor/processmaker/packages/mix-test-pkg');

        $this->assertStringContainsString('/storage/vendor/processmaker/packages/mix-test-pkg/js/widget.js?id=tenant-hash', $url);
        $this->assertStringNotContainsString('/storage/tenant_', $url);

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
