<?php

namespace ProcessMaker\Listeners;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Console\Scheduling\Schedule;
use ProcessMaker\Multitenancy\Services\TenantSchedulingService;
use Spatie\Multitenancy\Contracts\IsTenant;

class RegisterTenantScheduleTasks
{
    protected $schedule;

    public function __construct(Schedule $schedule)
    {
        $this->schedule = $schedule;
    }

    public function handle(CommandStarting $event)
    {
        if ($event->command === 'schedule:run') {
            $tenant = app(IsTenant::class)::current();
            if (!$tenant) {
                return;
            }
            app(TenantSchedulingService::class)->registerScheduledTasksForTenant($this->schedule, $tenant);
        }
    }
}
