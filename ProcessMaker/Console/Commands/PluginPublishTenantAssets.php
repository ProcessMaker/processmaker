<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use ProcessMaker\Managers\TenantPluginAssetPublisher;
class PluginPublishTenantAssets extends Command
{
    /**
     * @var string
     */
    protected $signature = 'plugin:publish-tenant-assets
                            {pluginFolder? : Plugin directory name under storage/plugins (omit for all enabled plugins)}';

    /**
     * @var string
     */
    protected $description = 'Copy plugin public assets into public/storage/tenant_{id}/vendor/... for the current tenant';

    public function handle(TenantPluginAssetPublisher $publisher): int
    {
        if (!config('app.multitenancy')) {
            $this->error('Multitenancy is disabled. This command only applies when MULTITENANCY=true.');

            return self::FAILURE;
        }

        $tenant = app()->bound('currentTenant') ? app('currentTenant') : null;
        if (!$tenant || !isset($tenant->id)) {
            $this->error('This command must be run within a tenant context (TENANT=<id> or tenants:artisan).');

            return self::FAILURE;
        }

        $pluginFolder = $this->argument('pluginFolder');

        if ($pluginFolder === null) {
            $this->info('Publishing plugin assets for tenant ' . $tenant->id . '...');
            $results = $publisher->publishAllInstalledPlugins((int) $tenant->id);

            if ($results === []) {
                $this->warn('No plugin directories found under ' . storage_path('plugins'));

                return self::SUCCESS;
            }

            foreach ($results as $folder => $published) {
                $this->line(
                    $folder . ': ' . ($published ? 'published' : 'skipped (no public/)')
                );
            }

            return self::SUCCESS;
        }

        $pluginPath = storage_path('plugins/' . $pluginFolder);
        if (!is_dir($pluginPath)) {
            $this->error('Plugin not found: ' . $pluginPath);

            return self::FAILURE;
        }

        $published = $publisher->publishPlugin($pluginPath, (int) $tenant->id);

        if ($published) {
            $this->info('Published assets for plugin ' . $pluginFolder . ' (tenant ' . $tenant->id . ').');
        } else {
            $this->warn('Plugin has no public/ directory; nothing copied.');
        }

        return self::SUCCESS;
    }
}
