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

        $this->warnIfDryRun($dryRun);

        $result = $this->runSync($sync, $dryRun);
        if ($result === null) {
            return self::FAILURE;
        }

        $this->displaySyncOverview($result);
        $this->displayTenantPlans($result);
        $this->displayFileChanges($result, $dryRun);

        return $this->finishWithSupervisor($sync, $dryRun);
    }

    private function warnIfDryRun(bool $dryRun): void
    {
        if ($dryRun) {
            $this->warn('Dry run: no files will be written or removed.');
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function runSync(OctaneTenantRuntimeSync $sync, bool $dryRun): ?array
    {
        try {
            return $sync->sync($dryRun);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function displaySyncOverview(array $result): void
    {
        $this->info('Mode: ' . $result['mode']);
        $this->line('Supervisor dir: ' . config('octane_tenants.supervisor_dir'));
        $this->line('Edge Caddyfile: ' . config('octane_tenants.edge_caddyfile'));
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function displayTenantPlans(array $result): void
    {
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

            return;
        }

        if ($result['mode'] === 'multitenant') {
            $this->warn('No tenants found in the landlord database.');
        }
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function displayFileChanges(array $result, bool $dryRun): void
    {
        $this->displayPathList($result['written'], $dryRun, '+', 'Would write', 'Wrote');
        $this->displayPathList($result['removed'], $dryRun, '-', 'Would remove', 'Removed');
    }

    /**
     * @param  list<string>  $paths
     */
    private function displayPathList(
        array $paths,
        bool $dryRun,
        string $marker,
        string $dryRunLabel,
        string $appliedLabel,
    ): void {
        if ($paths === []) {
            return;
        }

        $this->info(($dryRun ? $dryRunLabel : $appliedLabel) . ':');
        foreach ($paths as $path) {
            $this->line('  ' . $marker . ' ' . $path);
        }
    }

    private function finishWithSupervisor(OctaneTenantRuntimeSync $sync, bool $dryRun): int
    {
        if (! $this->option('apply')) {
            $this->comment('Files synced. Run with --apply to execute supervisorctl reread/update.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn('Skipping --apply during dry run.');

            return self::SUCCESS;
        }

        return $this->applySupervisor($sync);
    }

    private function applySupervisor(OctaneTenantRuntimeSync $sync): int
    {
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

        return self::SUCCESS;
    }
}