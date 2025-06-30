<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use ProcessMaker\Multitenancy\Tenant;

class TenantsAddScheduleTask extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:add-schedule-task 
                           {--tenant= : The ID of the tenant}
                           {--command= : The command to schedule}
                           {--frequency=daily : The frequency to run the command (e.g., everyMinute, hourly, daily, weekly, monthly, custom)}
                           {--cron= : Custom cron expression (required if frequency is custom)}
                           {--options= : Comma-separated list of options (withoutOverlapping, onOneServer)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a scheduled task to a tenant configuration';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $tenantId = $this->option('tenant');
        $command = $this->option('command');
        $frequency = $this->option('frequency');
        $cron = $this->option('cron');
        $optionsString = $this->option('options');

        // Validate required parameters
        if (!$tenantId) {
            $this->error('Tenant ID is required.');

            return 1;
        }

        if (!$command) {
            $this->error('Command is required.');

            return 1;
        }

        if ($frequency === 'custom' && !$cron) {
            $this->error('Cron expression is required when frequency is custom.');

            return 1;
        }

        // Find the tenant
        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            $this->error("Tenant with ID {$tenantId} not found.");

            return 1;
        }

        // Get current config or initialize empty array
        $config = $tenant->config ?? [];

        // Initialize schedule config if not exists
        if (!isset($config['schedule'])) {
            $config['schedule'] = [];
        }

        // Set up the task configuration
        $taskConfig = [
            'frequency' => $frequency,
        ];

        // Add cron expression if using custom frequency
        if ($frequency === 'custom') {
            $taskConfig['cron'] = $cron;
        }

        // Process options
        if ($optionsString) {
            $options = explode(',', $optionsString);
            foreach ($options as $option) {
                $option = trim($option);
                if ($option === 'withoutOverlapping' || $option === 'onOneServer') {
                    $taskConfig[$option] = true;
                }
            }
        }

        // Add or update the command in the tenant's schedule config
        $config['schedule'][$command] = $taskConfig;

        // Save the updated configuration
        $tenant->config = $config;
        $tenant->save();

        $this->info("Scheduled task '{$command}' added to tenant {$tenantId} with frequency '{$frequency}'.");

        return 0;
    }
}
