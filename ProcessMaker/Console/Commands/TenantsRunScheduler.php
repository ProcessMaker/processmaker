<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use ProcessMaker\Multitenancy\Services\TenantSchedulingService;
use ProcessMaker\Multitenancy\Tenant;

class TenantsRunScheduler extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:run-scheduler {--tenant= : The ID of the tenant}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run tenant-specific scheduler for testing purposes';

    /**
     * The schedule instance.
     *
     * @var Schedule
     */
    protected $schedule;

    /**
     * The tenant schedule manager.
     *
     * @var TenantSchedulingService
     */
    protected $service;

    /**
     * Create a new command instance.
     *
     * @param  Schedule  $schedule
     * @param  TenantSchedulingService  $service
     * @return void
     */
    public function __construct(Schedule $schedule, TenantSchedulingService $service)
    {
        parent::__construct();

        $this->schedule = $schedule;
        $this->service = $service;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $tenantId = $this->option('tenant');

        if (!$tenantId) {
            $this->error('Tenant ID is required.');

            return 1;
        }

        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            $this->error("Tenant with ID {$tenantId} not found.");

            return 1;
        }

        $this->info("Registering scheduled tasks for tenant {$tenantId}...");

        // Register tenant-specific schedule
        $this->service->registerScheduledTasksForTenant($this->schedule, $tenant);

        // Get due events
        $dueEvents = $this->schedule->dueEvents(app());

        if (empty($dueEvents)) {
            $this->info("No due events found for tenant {$tenantId} at this time.");

            return 0;
        }

        $this->info('The following events are due to run:');

        foreach ($dueEvents as $event) {
            $this->info('  - ' . $event->command);

            if ($this->confirm('Do you want to run this command now?')) {
                $this->info('Running: ' . $event->command);
                $event->run(app());
            }
        }

        return 0;
    }
}
