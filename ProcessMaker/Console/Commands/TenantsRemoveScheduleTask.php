<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use ProcessMaker\Multitenancy\Tenant;

class TenantsRemoveScheduleTask extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:remove-schedule-task 
                           {--tenant= : The ID of the tenant}
                           {--command= : The command to remove from schedule}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove a scheduled task from a tenant configuration';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $tenantId = $this->option('tenant');
        $command = $this->option('command');

        // Validate required parameters
        if (!$tenantId) {
            $this->error('Tenant ID is required.');

            return 1;
        }

        if (!$command) {
            $this->error('Command is required.');

            return 1;
        }

        // Find the tenant
        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            $this->error("Tenant with ID {$tenantId} not found.");

            return 1;
        }

        // Get current config
        $config = $tenant->config ?? [];

        // Check if schedule and command exist
        if (!isset($config['schedule']) || !isset($config['schedule'][$command])) {
            $this->error("Command '{$command}' not found in tenant {$tenantId}'s schedule configuration.");

            return 1;
        }

        // Remove the command from the schedule
        unset($config['schedule'][$command]);

        // If schedule is empty, remove it
        if (empty($config['schedule'])) {
            unset($config['schedule']);
        }

        // Save the updated configuration
        $tenant->config = $config;
        $tenant->save();

        $this->info("Scheduled task '{$command}' removed from tenant {$tenantId}.");

        return 0;
    }
}
