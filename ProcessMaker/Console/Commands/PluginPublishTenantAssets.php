<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use ProcessMaker\Managers\TenantPluginAssetPublisher;
use ProcessMaker\Support\PluginVendorAssets;

class PluginPublishTenantAssets extends Command
{
    /**
     * @var string
     */
    protected $signature = 'plugin:publish-tenant-assets
                            {pluginFolder? : Plugin folder name under storage/plugins (omit for all installed plugins)}';

    /**
     * @var string
     */
    protected $description = 'Copy plugin public assets onto the current tenant public disk (storage/tenant_{id}/app/public/vendor/...)';

    public function handle(TenantPluginAssetPublisher $publisher): int
    {
        if (!config('app.multitenancy')) {
            $this->error('Multitenancy is disabled. This command only applies when MULTITENANCY=true.');

            return self::FAILURE;
        }

        $tenantId = PluginVendorAssets::currentTenantId();
        if ($tenantId === null) {
            $this->error('No current tenant. Run with TENANT=<id> or through tenants:artisan.');

            return self::FAILURE;
        }

        $folder = $this->argument('pluginFolder');

        if ($folder) {
            $pluginPath = storage_path('plugins/' . $folder);
            if (!is_dir($pluginPath)) {
                $this->error("Plugin folder not found: {$pluginPath}");

                return self::FAILURE;
            }

            $published = $publisher->publishPlugin($pluginPath, $tenantId);
            $this->info($published
                ? "Published {$folder} for tenant {$tenantId}."
                : "Skipped {$folder}: no public directory.");

            return self::SUCCESS;
        }

        foreach ($publisher->publishAllInstalledPlugins($tenantId) as $name => $published) {
            $this->line(($published ? 'Published ' : 'Skipped   ') . $name);
        }

        return self::SUCCESS;
    }
}
