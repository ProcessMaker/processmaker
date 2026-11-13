<?php

namespace Tests\Unit\Traits;

use Illuminate\Support\ServiceProvider;
use ProcessMaker\Events\ModelerStarting;
use ProcessMaker\Managers\ModelerManager;
use ProcessMaker\Support\PluginVendorAssets;
use ProcessMaker\Traits\PluginServiceProviderTrait;
use Tests\TestCase;

class PluginServiceProviderTraitModelerTest extends TestCase
{
    public function testModelerStartingRegistersScriptWhenOnlyTenantAssetsExist(): void
    {
        config(['app.multitenancy' => true]);
        config(['plugin_manager.tenant_plugin_assets_enabled' => true]);
        $this->app->instance('currentTenant', (object) ['id' => 8]);

        $package = 'modeler-trait-pkg-' . uniqid();
        $tenantRoot = PluginVendorAssets::tenantPublicRoot(8, $package);
        mkdir($tenantRoot . '/js', 0755, true);
        file_put_contents($tenantRoot . '/mix-manifest.json', json_encode([
            '/js/modeler-schema-profile.js' => '/js/modeler-schema-profile.js?id=trait-test',
        ]));

        $provider = new class(app()) extends ServiceProvider {
            use PluginServiceProviderTrait;

            public function runModelerStarting(ModelerManager $manager, string $package): void
            {
                $this->registerModelerScript(
                    'js/modeler-schema-profile.js',
                    'vendor/processmaker/packages/' . $package,
                    ['type' => 'module']
                );
                $this->modelerStarting(new ModelerStarting($manager));
            }
        };

        $manager = new ModelerManager();
        $provider->runModelerStarting($manager, $package);

        $scripts = $manager->getScriptWithParams();
        $found = false;
        foreach ($scripts as $script) {
            if (str_contains($script['src'], '/storage/tenant_8/vendor/processmaker/packages/' . $package)) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Expected tenant modeler script URL in manager registry');

        $this->deleteDirectory($tenantRoot);
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
