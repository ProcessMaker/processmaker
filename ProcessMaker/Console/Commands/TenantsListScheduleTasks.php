<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use ProcessMaker\Multitenancy\Tenant;

class TenantsListScheduleTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:list-schedule-tasks {--tenant= : The ID of the tenant}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all scheduled tasks for a tenant';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $tenantId = $this->option('tenant');

        // Validate tenant ID
        if (!$tenantId) {
            $this->error('Tenant ID is required.');

            return 1;
        }

        // Find the tenant
        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            $this->error("Tenant with ID {$tenantId} not found.");

            return 1;
        }

        // Get tenant configuration
        $config = $tenant->config ?? [];

        // Check if schedule configuration exists
        if (!isset($config['schedule']) || empty($config['schedule'])) {
            $this->info("No scheduled tasks found for tenant {$tenantId}.");

            return 0;
        }

        // Prepare data for table
        $tasks = [];
        foreach ($config['schedule'] as $command => $settings) {
            $frequency = $settings['frequency'];
            if ($frequency === 'custom') {
                $frequency .= " ({$settings['cron']})";
            }

            $options = [];
            if (isset($settings['withoutOverlapping']) && $settings['withoutOverlapping']) {
                $options[] = 'withoutOverlapping';
            }
            if (isset($settings['onOneServer']) && $settings['onOneServer']) {
                $options[] = 'onOneServer';
            }

            $tasks[] = [
                'command' => $command,
                'frequency' => $frequency,
                'options' => implode(', ', $options),
            ];
        }

        // Display tasks in a table
        $this->info("Scheduled tasks for tenant {$tenantId}:");
        $this->table(
            ['Command', 'Frequency', 'Options'],
            $tasks
        );

        return 0;
    }
}
