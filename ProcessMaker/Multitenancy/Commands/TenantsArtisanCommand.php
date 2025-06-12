<?php

namespace ProcessMaker\Multitenancy\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use ProcessMaker\Multitenancy\Services\TenantSchedulingService;
use ProcessMaker\Multitenancy\Tenant;
use Spatie\Multitenancy\Commands\Concerns\TenantAware;
use Spatie\Multitenancy\Concerns\UsesMultitenancyConfig;
use Spatie\Multitenancy\Contracts\IsTenant;

class TenantsArtisanCommand extends Command
{
    use UsesMultitenancyConfig;
    use TenantAware;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:artisan {artisanCommand} {--tenant=*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run an Artisan command for the specified tenant(s)';

    /**
     * The scheduler instance.
     *
     * @var Schedule
     */
    protected $schedule;

    /**
     * Create a new command instance.
     *
     * @param Schedule $schedule
     * @return void
     */
    public function __construct(Schedule $schedule)
    {
        parent::__construct();

        $this->schedule = $schedule;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        if (!$artisanCommand = $this->argument('artisanCommand')) {
            $artisanCommand = $this->ask('Which artisan command do you want to run for all tenants?');
        }

        $artisanCommand = addslashes($artisanCommand);

        $tenantIds = $this->option('tenant');

        // If no specific tenant IDs were provided, use the current tenant
        if (empty($tenantIds)) {
            $tenant = app(IsTenant::class)::current();
            if (!$tenant) {
                $this->error('No tenant specified and no current tenant found.');

                return 1;
            }
            $tenants = collect([$tenant]);
        } else {
            // Otherwise, get the specified tenants
            $tenants = Tenant::whereIn('id', $tenantIds)->get();
            if ($tenants->isEmpty()) {
                $this->error('No tenants found with the specified IDs.');

                return 1;
            }
        }

        // Process each tenant
        foreach ($tenants as $tenant) {
            $this->line('');
            $this->info("Running command for tenant `{$tenant->name}` (id: {$tenant->id})...");
            $this->line('---------------------------------------------------------');

            // Set current tenant for the command execution
            if (method_exists($tenant, 'makeCurrent')) {
                $tenant->makeCurrent();
            }
            app()->instance('currentTenant', $tenant);

            // Special handling for schedule:run command
            if ($artisanCommand === 'schedule:run') {
                // Create a fresh schedule instance
                $freshSchedule = app()->make(Schedule::class);
                // Register scheduled tasks for the tenant
                app(TenantSchedulingService::class)->registerScheduledTasksForTenant($freshSchedule, $tenant);
                // Run the schedule:run command
                Artisan::call($artisanCommand, [], $this->output);
            } else {
                // For other commands, just run through Artisan
                // Don't automatically add the tenant parameter since not all commands support it
                try {
                    Artisan::call($artisanCommand, [], $this->output);
                } catch (\Exception $e) {
                    $this->error('Error executing command: ' . $e->getMessage());
                    // Continue with other tenants if this one fails
                }
            }
        }

        return 0;
    }
}
