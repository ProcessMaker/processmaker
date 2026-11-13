<?php

declare(strict_types=1);

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use ProcessMaker\Multitenancy\Services\OctaneTenantRuntimeSync;
use Throwable;

class TenantsOctaneSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:octane-sync
                            {--dry-run : Show what would be written without changing files}
                            {--apply : Run supervisorctl reread/update after writing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Caddyfile.edge and supervisor Octane programs for multitenancy (or restore single Octane)';

    /**
     * Execute the console command.
     */
    public function handle(OctaneTenantRuntimeSync $sync): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Dry run: no files will be written or removed.');
        }

        try {
            $result = $sync->sync($dryRun);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Mode: ' . $result['mode']);
        $this->line('Supervisor dir: ' . config('octane_tenants.supervisor_dir'));
        $this->line('Edge Caddyfile: ' . config('octane_tenants.edge_caddyfile'));

        if ($result['tenants'] !== []) {
            $this->table(
                ['Tenant ID', 'Domain', 'Octane port', 'Admin port'],
                collect($result['tenants'])->map(fn (array $plan) => [
                    $plan['id'],
                    $plan['domain'],
                    $plan['port'],
                    $plan['admin_port'],
                ])->all()
            );
        } elseif ($result['mode'] === 'multitenant') {
            $this->warn('No tenants found in the landlord database.');
        }

        if ($result['written'] !== []) {
            $this->info(($dryRun ? 'Would write' : 'Wrote') . ':');
            foreach ($result['written'] as $path) {
                $this->line('  + ' . $path);
            }
        }

        if ($result['removed'] !== []) {
            $this->info(($dryRun ? 'Would remove' : 'Removed') . ':');
            foreach ($result['removed'] as $path) {
                $this->line('  - ' . $path);
            }
        }

        if ($this->option('apply')) {
            if ($dryRun) {
                $this->warn('Skipping --apply during dry run.');

                return self::SUCCESS;
            }

            try {
                $output = $sync->applySupervisor();
            } catch (Throwable $exception) {
                $this->error($exception->getMessage());

                return self::FAILURE;
            }

            if ($output !== '') {
                $this->line($output);
            }
            $this->info('supervisorctl reread/update completed.');
        } else {
            $this->comment('Files synced. Run with --apply to execute supervisorctl reread/update.');
        }

        return self::SUCCESS;
    }
}
