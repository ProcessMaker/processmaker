<?php

namespace ProcessMaker\Multitenancy\Services;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use ProcessMaker\Multitenancy\Tenant;

class TenantSchedulingService
{
    /**
     * Register scheduled tasks for all tenants.
     *
     * @param  Schedule  $schedule
     * @return void
     */
    public function registerTenantScheduledTasks(Schedule $schedule)
    {
        // Get tenant ID from the current request or use all tenants if not specified
        $id = $this->getTenantIdFromCommand();
        // Get all tenants by id or all

        $tenants = $id ? Tenant::where('id', $id)->get() : Tenant::all();

        // For each tenant, register their specific scheduled tasks
        foreach ($tenants as $tenant) {
            $this->registerScheduledTasksForTenant($schedule, $tenant);
        }
    }

    /**
     * Get tenant ID from command line arguments
     *
     * @return int|null
     */
    protected function getTenantIdFromCommand()
    {
        // Get tenant ID from the request
        if (Request::has('tenant')) {
            return Request::get('tenant');
        }

        // Check if running in console with --tenant option
        if (app()->runningInConsole() && isset($_SERVER['argv'])) {
            $args = $_SERVER['argv'];
            foreach ($args as $index => $arg) {
                if ($arg === '--tenant' && isset($args[$index + 1])) {
                    return (int) $args[$index + 1];
                }

                if (strpos($arg, '--tenant=') === 0) {
                    return (int) substr($arg, 9);
                }
            }
        }

        return null;
    }

    /**
     * Register scheduled tasks for a specific tenant.
     *
     * @param Schedule $schedule
     * @param Tenant $tenant
     * @return void
     */
    public function registerScheduledTasksForTenant(Schedule $schedule, Tenant $tenant)
    {
        // Get the tenant's configuration
        $config = $tenant->config ?? [];

        // Debug information
        Log::info("Registering tasks for tenant: {$tenant->id} - " . ($tenant->name ?? 'Unnamed'));
        Log::info('Tenant config schedule: ' . json_encode($config['schedule'] ?? []));

        if (!isset($config['schedule']) || empty($config['schedule'])) {
            Log::info("No scheduled tasks configured for tenant {$tenant->id}");

            return;
        }

        // Make sure the tenant is set as the current tenant
        $currentTenantId = app()->bound('currentTenantId') ? app('currentTenantId') : null;
        Log::info('Current tenant ID in container: ' . ($currentTenantId ?? 'not set'));

        // Only proceed if we're handling the correct tenant or no specific tenant is set
        if ($currentTenantId !== null && $currentTenantId != $tenant->id) {
            Log::info("Skipping tasks for tenant {$tenant->id} as current tenant is {$currentTenantId}");

            return;
        }

        foreach ($config['schedule'] as $command => $settings) {
            Log::info("Registering command for tenant {$tenant->id}: {$command}");
            $scheduledEvent = $this->createScheduledEvent($schedule, $tenant, $command);
            $this->applyScheduleFrequency($scheduledEvent, $settings);
            $this->applyScheduleOptions($scheduledEvent, $settings);

            // Add a descriptive description with tenant info
            $scheduledEvent->description("Tenant {$tenant->id} ({$tenant->name}): {$command}");

            // Remove the environments restriction which might be preventing execution
            // $scheduledEvent->environments(['tenant_' . $tenant->id]);
        }

        Log::info('Registered ' . count($config['schedule']) . " tasks for tenant {$tenant->id}");
    }

    /**
     * Create a scheduled event for a tenant command.
     *
     * @param Schedule $schedule
     * @param Tenant $tenant
     * @param string $command
     * @return \Illuminate\Console\Scheduling\Event
     */
    protected function createScheduledEvent(Schedule $schedule, Tenant $tenant, string $command)
    {
        // Some commands may not support the tenant option, so wrap them in a closure
        // that sets the tenant context first
        $event = $schedule->call(function () use ($command, $tenant) {
            // Set tenant context
            if (method_exists($tenant, 'makeCurrent')) {
                $tenant->makeCurrent();
            }
            app()->instance('currentTenant', $tenant);

            // Log that we're about to run the command
            Log::info("Executing scheduled command for tenant {$tenant->id}: {$command}");

            // Execute the command
            Artisan::call($command);
        });

        // Log the event creation
        Log::info("Created scheduled event for tenant {$tenant->id}: {$command}");

        return $event;
    }

    /**
     * Apply frequency settings to a scheduled event.
     *
     * @param \Illuminate\Console\Scheduling\Event $event
     * @param array $settings
     * @return void
     */
    protected function applyScheduleFrequency($event, array $settings)
    {
        $frequency = $settings['frequency'] ?? 'daily';

        if ($frequency === 'custom' && isset($settings['cron'])) {
            $event->cron($settings['cron']);

            return;
        }

        switch ($frequency) {
            case 'everyMinute':
                $event->everyMinute();
                break;
            case 'hourly':
                $event->hourly();
                break;
            case 'daily':
                $event->daily();
                break;
            case 'weekly':
                $event->weekly();
                break;
            case 'monthly':
                $event->monthly();
                break;
            default:
                $event->daily();
                break;
        }
    }

    /**
     * Apply additional options to a scheduled event.
     *
     * @param \Illuminate\Console\Scheduling\Event $event
     * @param array $settings
     * @return void
     */
    protected function applyScheduleOptions($event, array $settings)
    {
        if (isset($settings['withoutOverlapping']) && $settings['withoutOverlapping']) {
            $event->withoutOverlapping();
        }

        if (isset($settings['onOneServer']) && $settings['onOneServer']) {
            $event->onOneServer();
        }
    }
}
