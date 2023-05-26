<?php

namespace ProcessMaker\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

/**
 * Register our Events and their Listeners
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     * @var array
     */
    protected $listen = [
        'Illuminate\Auth\Events\Failed' => [
            'ProcessMaker\Listeners\SecurityLogger',
        ],
        'Illuminate\Auth\Events\Login' => [
            'ProcessMaker\Listeners\LoginListener',
            'ProcessMaker\Listeners\SecurityLogger',
        ],
        'Illuminate\Auth\Events\Logout' => [
            'ProcessMaker\Listeners\SecurityLogger',
        ],
        'Illuminate\Database\Events\MigrationsEnded' => [
            'ProcessMaker\Listeners\UpdateDataLakeViews',
        ],
        'ProcessMaker\Events\CategoryCreated' => [
            'ProcessMaker\Listeners\SecurityLogger',
        ],
        'ProcessMaker\Events\CategoryDeleted' => [
            'ProcessMaker\Listeners\SecurityLogger',
        ],
        'ProcessMaker\Events\CategoryUpdated' => [
            'ProcessMaker\Listeners\SecurityLogger',
        ],
        'ProcessMaker\Events\EnvironmentVariablesCreated' => [
            'ProcessMaker\Listeners\SecurityLogger',
        ],
        'ProcessMaker\Events\EnvironmentVariablesDeleted' => [
            'ProcessMaker\Listeners\SecurityLogger',
        ],
        'ProcessMaker\Events\EnvironmentVariablesUpdated' => [
            'ProcessMaker\Listeners\SecurityLogger',
        ],
        'ProcessMaker\Events\GroupCreated' => [
            'ProcessMaker\Listeners\SecurityLogger',
        ],
        'ProcessMaker\Events\GroupDeleted' => [
            'ProcessMaker\Listeners\SecurityLogger',
        ],
        'ProcessMaker\Events\PermissionUpdated' => [
            'ProcessMaker\Listeners\SecurityLogger',
        ],
        'ProcessMaker\Events\ProcessUpdated' => [
            'ProcessMaker\Listeners\SecurityLogger',
        ],
        'ProcessMaker\Events\ScreenCreated' => [
            'ProcessMaker\Listeners\SecurityLogger',
        ],
        'ProcessMaker\Events\ScreenDeleted' => [
            'ProcessMaker\Listeners\SecurityLogger',
        ],
        'ProcessMaker\Events\ScreenUpdated' => [
            'ProcessMaker\Listeners\SecurityLogger',
        ],
        'ProcessMaker\Events\SessionStarted' => [
            'ProcessMaker\Listeners\ActiveUserListener',
        ],
        'ProcessMaker\Events\SettingsUpdated' => [
            'ProcessMaker\Listeners\SecurityLogger',
        ],
        'ProcessMaker\Events\TemplateCreated' => [
            'ProcessMaker\Listeners\SecurityLogger',
        ],
        'ProcessMaker\Events\TemplateDeleted' => [
            'ProcessMaker\Listeners\SecurityLogger',
        ],
        'ProcessMaker\Events\TemplateUpdated' => [
            'ProcessMaker\Listeners\SecurityLogger',
        ],
        'ProcessMaker\Events\UserCreated' => [
            'ProcessMaker\Listeners\SecurityLogger',
        ],
        'ProcessMaker\Events\UserDeleted' => [
            'ProcessMaker\Listeners\SecurityLogger',
        ],
        'ProcessMaker\Events\UserUpdated' => [
            'ProcessMaker\Listeners\SecurityLogger',
        ],
    ];

    /**
     * Register any events for your application.
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }
}
