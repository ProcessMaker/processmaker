<?php

namespace Tests\Unit\Providers;

use Illuminate\Foundation\Mix;
use ProcessMaker\Support\PluginVendorAssets;
use Tests\TestCase;

class MixTenantPluginVendorTest extends TestCase
{
    public function testMixTenantPluginVendorResolutionAndFallback(): void
    {
        config(['app.multitenancy' => true]);
        config(['plugin_manager.tenant_plugin_assets_enabled' => true]);
        $this->app->instance('currentTenant', (object) ['id' => 4]);

        $tenantPackage = 'mix-test-pkg';
        $tenantRoot = PluginVendorAssets::tenantPublicRoot(4, $tenantPackage);
        mkdir($tenantRoot . '/js', 0755, true);
        file_put_contents($tenantRoot . '/mix-manifest.json', json_encode([
            '/js/widget.js' => '/js/widget.js?id=tenant-hash-4',
        ]));

        $globalRoot = PluginVendorAssets::globalPublicRoot($tenantPackage);
        if (!is_dir(dirname($globalRoot))) {
            mkdir(dirname($globalRoot), 0755, true);
        }
        if (!is_dir($globalRoot)) {
            mkdir($globalRoot . '/js', 0755, true);
        }
        file_put_contents($globalRoot . '/mix-manifest.json', json_encode([
            '/js/widget.js' => '/js/widget.js?id=global-hash',
        ]));

        $tenantUrl = (string) app(Mix::class)(
            '/js/widget.js',
            'vendor/processmaker/packages/mix-test-pkg'
        );
        $this->assertStringContainsString('/storage/tenant_4/vendor/processmaker/packages/mix-test-pkg', $tenantUrl);
        $this->assertStringContainsString('tenant-hash-4', $tenantUrl);
        $this->assertStringNotContainsString('global-hash', $tenantUrl);

        $this->deleteDirectory($tenantRoot);

        $fallbackPackage = 'mix-fallback-pkg';
        $fallbackGlobalRoot = PluginVendorAssets::globalPublicRoot($fallbackPackage);
        mkdir($fallbackGlobalRoot . '/js', 0755, true);
        file_put_contents($fallbackGlobalRoot . '/mix-manifest.json', json_encode([
            '/js/fallback.js' => '/js/fallback.js?id=global-only',
        ]));

        $fallbackUrl = (string) app(Mix::class)(
            '/js/fallback.js',
            'vendor/processmaker/packages/mix-fallback-pkg'
        );
        $this->assertStringContainsString('/vendor/processmaker/packages/mix-fallback-pkg', $fallbackUrl);
        $this->assertStringContainsString('global-only', $fallbackUrl);

        $this->deleteDirectory($globalRoot);
        $this->deleteDirectory($fallbackGlobalRoot);
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
