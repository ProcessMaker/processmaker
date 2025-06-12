<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use ProcessMaker\Multitenancy\Services\TenantSchedulingService;
use Spatie\Multitenancy\Commands\Concerns\TenantAware;
use Spatie\Multitenancy\Concerns\UsesMultitenancyConfig;
use Spatie\Multitenancy\Contracts\IsTenant;

class TenantsArtisanCommand extends Command
{
    use UsesMultitenancyConfig;
    use TenantAware;

    protected $schedule;

    protected $signature = 'tenants:artisan {artisanCommand} {--tenant=*}';

    public function __construct(Schedule $schedule)
    {
        parent::__construct();

        $this->schedule = $schedule;
    }

    public function handle(): void
    {
        if (!$artisanCommand = $this->argument('artisanCommand')) {
            $artisanCommand = $this->ask('Which artisan command do you want to run for all tenants?');
        }

        $artisanCommand = addslashes($artisanCommand);

        $tenant = app(IsTenant::class)::current();

        $this->line('');
        $this->info("Runnin Wrapper command for tenant `{$tenant->name}` (id: {$tenant->getKey()})...");
        $this->line('---------------------------------------------------------');
        if ($artisanCommand === 'schedule:run') {
            app(TenantSchedulingService::class)->registerScheduledTasksForTenant($this->schedule, $tenant);
        }
        Artisan::call($artisanCommand, [], $this->output);
    }
}
