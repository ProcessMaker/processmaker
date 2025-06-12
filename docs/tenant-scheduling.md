# Tenant Scheduling in ProcessMaker

This guide explains how ProcessMaker handles scheduled tasks in a multi-tenant environment using Laravel's scheduler and Spatie's multitenancy package.

## Overview

ProcessMaker uses a service-based architecture for tenant scheduling that provides:

1. **Tenant-specific scheduled tasks** - Each tenant can have its own set of scheduled tasks
2. **Flexible scheduling options** - Support for various frequency options and scheduling settings
3. **Isolated execution** - Tasks run in the context of their specific tenant
4. **Command-line management** - Easy-to-use commands for managing tenant tasks

## Architecture

The tenant scheduling system consists of:

1. **TenantSchedulingService** - Core service that manages tenant scheduling
2. **TenantSchedulingServiceProvider** - Service provider that registers the service
3. **Console Kernel** - Integrates with Laravel's scheduler

## How It Works

1. A global scheduler runs `schedule:run` every minute from the cron job
2. The scheduler integrates with the `TenantSchedulingService` to process tenant-specific tasks
3. The service detects the tenant context (either from command-line arguments or processes all tenants)
4. Tasks are executed in the appropriate tenant context using `tenants:artisan` commands

## Configuration

Scheduled tasks are configured in each tenant's `config` JSON field under the `schedule` key:

```json
{
  "config": {
    "schedule": {
      "package-auth:ldap-sync": {
        "frequency": "daily",
        "withoutOverlapping": true
      },
      "processmaker:sync-recommendations": {
        "frequency": "custom",
        "cron": "0 */4 * * *",
        "onOneServer": true
      }
    }
  }
}
```

### Available Frequency Options

- `everyMinute`
- `everyFiveMinutes`
- `everyTenMinutes`
- `everyFifteenMinutes`
- `everyThirtyMinutes`
- `hourly`
- `daily`
- `weekly`
- `monthly`
- `quarterly`
- `yearly`
- `custom` (requires a `cron` value)

### Additional Options

- `withoutOverlapping`: Prevent task overlapping (boolean)
- `onOneServer`: Run task on only one server (boolean)
- `cron`: Custom cron expression (string, required if frequency is 'custom')

## Managing Tenant Scheduled Tasks

### Adding Tasks

You can add tasks to a tenant's schedule using one of these methods:

#### 1. Using Artisan Command

```bash
php artisan tenant:add-schedule-task --tenant=123 --command="your:command" --frequency="daily" --options="withoutOverlapping,onOneServer"
```

#### 2. Using PHP Code

```php
use ProcessMaker\Multitenancy\Tenant;

// Find the tenant
$tenant = Tenant::find($tenantId);

// Get current config or initialize empty array
$config = $tenant->config ?? [];

// Add or update scheduled task configuration
$config['schedule']['your:command'] = [
    'frequency' => 'daily',
    'withoutOverlapping' => true,
    'onOneServer' => true,
];

// Save the updated configuration
$tenant->config = $config;
$tenant->save();
```

### Listing Tasks

To see all scheduled tasks for a tenant:

```bash
php artisan tenant:list-schedule-tasks --tenant=123
```

### Removing Tasks

To remove a task:

```bash
php artisan tenant:remove-schedule-task --tenant=123 --command="your:command"
```

Or programmatically:

```php
use ProcessMaker\Multitenancy\Tenant;

$tenant = Tenant::find($tenantId);
$config = $tenant->config ?? [];

if (isset($config['schedule']['command:to:remove'])) {
    unset($config['schedule']['command:to:remove']);
    $tenant->config = $config;
    $tenant->save();
}
```

## Running and Testing Tasks

### Running Tasks Manually

Run the scheduler for all tenants:

```bash
php artisan schedule:run
```

Or for a specific tenant:

```bash
php artisan tenants:artisan "schedule:run" --tenant=123
```

### Testing Tenant Schedules

To see which tasks would run at the current time:

```bash
php artisan tenants:artisan "schedule:list" --tenant=123
```

For verbose output that shows more details:

```bash
php artisan tenants:artisan "schedule:run --verbose" --tenant=123
```

## Server Setup

Add this to your crontab to run the scheduler every minute:

```
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## Troubleshooting

If scheduled tasks aren't running as expected:

1. **Verify tenant configuration** - Check that the tenant has proper schedule configuration
2. **Check command registration** - Ensure your command is properly registered
3. **Review logs** - Check Laravel logs for errors during execution
4. **Command execution** - Test the command directly to verify it works outside the scheduler
5. **Tenant context** - Verify that the tenant context is being properly set
6. **Database connections** - Ensure tenant database connections are working correctly

For failed commands like `bpmn:timer`, check if they need to be converted to tenant-specific commands by running them through the `tenants:artisan` command with the appropriate tenant ID.

## Advanced: Custom Implementation

The `TenantSchedulingService` can be extended or modified to implement custom scheduling logic for your specific needs. The service uses a modular design that separates:

1. Tenant detection logic (`getTenantIdFromCommand`)
2. Task registration logic (`registerScheduledTasksForTenant`)
3. Scheduling configuration (`applyScheduleFrequency`, `applyScheduleOptions`)

This separation makes it easy to customize or extend any part of the scheduling system. 