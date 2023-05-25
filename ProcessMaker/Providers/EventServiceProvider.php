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
        'ProcessMaker\Events\AuthClientUpdated' => [
            'ProcessMaker\Listeners\SecurityLogger',
        ],
        'ProcessMaker\Events\AuthClientCreated' => [
            'ProcessMaker\Listeners\SecurityLogger',
        ],
        'ProcessMaker\Events\AuthClientDeleted' => [
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
        'ProcessMaker\Events\ProcessUpdated' => [
            'ProcessMaker\Listeners\SecurityLogger',
        ],
        'ProcessMaker\Events\ScriptCreated' => [
            'ProcessMaker\Listeners\SecurityLogger',
        ],
        'ProcessMaker\Events\ScriptDeleted' => [
            'ProcessMaker\Listeners\SecurityLogger',
        ],
        'ProcessMaker\Events\ScriptDuplicated' => [
            'ProcessMaker\Listeners\SecurityLogger',
        ],
        'ProcessMaker\Events\ScriptExecutorCreated' => [
            'ProcessMaker\Listeners\SecurityLogger',
        ],
        'ProcessMaker\Events\ScriptExecutorDeleted' => [
            'ProcessMaker\Listeners\SecurityLogger',
        ],
        'ProcessMaker\Events\ScriptExecutorUpdated' => [
            'ProcessMaker\Listeners\SecurityLogger',
        ],
        'ProcessMaker\Events\ScriptUpdated' => [
            'ProcessMaker\Listeners\SecurityLogger',
        ],
        'ProcessMaker\Events\SessionStarted' => [
            'ProcessMaker\Listeners\ActiveUserListener',
        ],
        'ProcessMaker\Events\SettingsUpdated' => [
            'ProcessMaker\Listeners\SecurityLogger',
        ],
        'ProcessMaker\Events\TokenCreated' => [
            'ProcessMaker\Listeners\SecurityLogger',
        ],
        'ProcessMaker\Events\TokenDeleted' => [
            'ProcessMaker\Listeners\SecurityLogger',
        ],
        'ProcessMaker\Events\UnauthorizedAccessAttempt' => [
            'ProcessMaker\Listeners\SecurityLogger',
        ],
        'ProcessMaker\Events\UserCreated' => [
            'ProcessMaker\Listeners\SecurityLogger',
        ],
        'ProcessMaker\Events\UserDeleted' => [
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
