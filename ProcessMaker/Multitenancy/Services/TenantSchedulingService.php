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

        foreach ($config['schedule'] as $command => $settings) {
            Log::info("Registering command for tenant {$tenant->id}: {$command}");
            $scheduledEvent = $this->createScheduledEvent($schedule, $tenant, $command);
            $this->applyScheduleFrequency($scheduledEvent, $settings);
            $this->applyScheduleOptions($scheduledEvent, $settings);

            // Format the command for display
            $phpBinary = PHP_BINARY;
            $formattedCommand = "'{$phpBinary}' 'artisan' {$command} > '/dev/null' 2>&1";
            // Add a descriptive description with tenant info and formatted command
            $scheduledEvent->description("Tenant {$tenant->id} ({$tenant->name}): {$formattedCommand}");
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
        // Lanza el comando exacto para ese tenant
        $fullCommand = "tenants:artisan {$command} --tenant={$tenant->id}";

        $event = $schedule->command($fullCommand);

        Log::info("Created scheduled event for tenant {$tenant->id}: {$fullCommand}");

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
        if (isset($settings['withoutOverlapping']) && $settings['withoutOverlapping']
            && method_exists($event, 'name')
        ) {
            // Ensure a unique name is set before withoutOverlapping()
            $eventName = 'tenant-' . ($settings['tenant_id'] ?? 'unknown') . '-' . md5($event->description ?? microtime());
            $event->name($eventName);

            $event->withoutOverlapping();
        }

        if (isset($settings['onOneServer']) && $settings['onOneServer']) {
            $event->onOneServer();
        }
    }
}
