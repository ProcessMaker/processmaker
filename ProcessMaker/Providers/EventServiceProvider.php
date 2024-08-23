<?php

namespace ProcessMaker\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use ProcessMaker\Events\ActivityAssigned;
use ProcessMaker\Events\ActivityCompleted;
use ProcessMaker\Events\ActivityReassignment;
use ProcessMaker\Events\AuthClientCreated;
use ProcessMaker\Events\AuthClientDeleted;
use ProcessMaker\Events\AuthClientUpdated;
use ProcessMaker\Events\CategoryCreated;
use ProcessMaker\Events\CategoryDeleted;
use ProcessMaker\Events\CategoryUpdated;
use ProcessMaker\Events\CustomizeUiUpdated;
use ProcessMaker\Events\EnvironmentVariablesCreated;
use ProcessMaker\Events\EnvironmentVariablesDeleted;
use ProcessMaker\Events\EnvironmentVariablesUpdated;
use ProcessMaker\Events\FilesAccessed;
use ProcessMaker\Events\FilesCreated;
use ProcessMaker\Events\FilesDeleted;
use ProcessMaker\Events\FilesDownloaded;
use ProcessMaker\Events\FilesUpdated;
use ProcessMaker\Events\GroupCreated;
use ProcessMaker\Events\GroupDeleted;
use ProcessMaker\Events\GroupUpdated;
use ProcessMaker\Events\GroupUsersUpdated;
use ProcessMaker\Events\PermissionUpdated;
use ProcessMaker\Events\ProcessArchived;
use ProcessMaker\Events\ProcessCompleted;
use ProcessMaker\Events\ProcessCreated;
use ProcessMaker\Events\ProcessPublished;
use ProcessMaker\Events\ProcessRestored;
use ProcessMaker\Events\ProcessUpdated;
use ProcessMaker\Events\QueueManagementAccessed;
use ProcessMaker\Events\RequestAction;
use ProcessMaker\Events\RequestError;
use ProcessMaker\Events\ScreenCreated;
use ProcessMaker\Events\ScreenDeleted;
use ProcessMaker\Events\ScreenUpdated;
use ProcessMaker\Events\ScriptCreated;
use ProcessMaker\Events\ScriptDeleted;
use ProcessMaker\Events\ScriptDuplicated;
use ProcessMaker\Events\ScriptExecutorCreated;
use ProcessMaker\Events\ScriptExecutorDeleted;
use ProcessMaker\Events\ScriptExecutorUpdated;
use ProcessMaker\Events\ScriptUpdated;
use ProcessMaker\Events\SettingsUpdated;
use ProcessMaker\Events\SignalCreated;
use ProcessMaker\Events\SignalDeleted;
use ProcessMaker\Events\SignalUpdated;
use ProcessMaker\Events\TemplateCreated;
use ProcessMaker\Events\TemplateDeleted;
use ProcessMaker\Events\TemplatePublished;
use ProcessMaker\Events\TemplateUpdated;
use ProcessMaker\Events\TokenCreated;
use ProcessMaker\Events\TokenDeleted;
use ProcessMaker\Events\UnauthorizedAccessAttempt;
use ProcessMaker\Events\UserCreated;
use ProcessMaker\Events\UserDeleted;
use ProcessMaker\Events\UserGroupMembershipUpdated;
use ProcessMaker\Events\UserRestored;
use ProcessMaker\Events\UserUpdated;
use ProcessMaker\Listeners\HandleActivityAssignedInterstitialRedirect;
use ProcessMaker\Listeners\HandleActivityCompletedRedirect;
use ProcessMaker\Listeners\HandleEndEventRedirect;
use ProcessMaker\Listeners\SecurityLogger;
use ProcessMaker\Listeners\SessionControlSettingsUpdated;

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
            'ProcessMaker\Listeners\UserSession',
        ],
        'Illuminate\Auth\Events\Logout' => [
            'ProcessMaker\Listeners\SecurityLogger',
        ],
        'Illuminate\Database\Events\MigrationsEnded' => [
            'ProcessMaker\Listeners\UpdateDataLakeViews',
        ],
        'ProcessMaker\Events\SessionStarted' => [
            'ProcessMaker\Listeners\ActiveUserListener',
        ],
        SettingsUpdated::class => [
            SessionControlSettingsUpdated::class,
        ],
        ProcessCompleted::class => [
            HandleEndEventRedirect::class,
        ],
        ActivityCompleted::class => [
            HandleActivityCompletedRedirect::class,
        ],
        ActivityAssigned::class => [
            HandleActivityAssignedInterstitialRedirect::class,
        ],
    ];

    /**
     * Register any events for your application.
     * @return void
     */
    public function boot()
    {
        parent::boot();

        // Check if the variable security_log is enable
        if (config('app.security_log')) {
            $this->app['events']->listen(ActivityReassignment::class, SecurityLogger::class);
            $this->app['events']->listen(AuthClientCreated::class, SecurityLogger::class);
            $this->app['events']->listen(AuthClientDeleted::class, SecurityLogger::class);
            $this->app['events']->listen(AuthClientUpdated::class, SecurityLogger::class);
            $this->app['events']->listen(CategoryCreated::class, SecurityLogger::class);
            $this->app['events']->listen(CategoryDeleted::class, SecurityLogger::class);
            $this->app['events']->listen(CategoryUpdated::class, SecurityLogger::class);
            $this->app['events']->listen(CustomizeUiUpdated::class, SecurityLogger::class);
            $this->app['events']->listen(EnvironmentVariablesCreated::class, SecurityLogger::class);
            $this->app['events']->listen(EnvironmentVariablesDeleted::class, SecurityLogger::class);
            $this->app['events']->listen(EnvironmentVariablesUpdated::class, SecurityLogger::class);
            $this->app['events']->listen(FilesAccessed::class, SecurityLogger::class);
            $this->app['events']->listen(FilesCreated::class, SecurityLogger::class);
            $this->app['events']->listen(FilesDeleted::class, SecurityLogger::class);
            $this->app['events']->listen(FilesDownloaded::class, SecurityLogger::class);
            $this->app['events']->listen(FilesUpdated::class, SecurityLogger::class);
            $this->app['events']->listen(GroupCreated::class, SecurityLogger::class);
            $this->app['events']->listen(GroupDeleted::class, SecurityLogger::class);
            $this->app['events']->listen(GroupUpdated::class, SecurityLogger::class);
            $this->app['events']->listen(GroupUsersUpdated::class, SecurityLogger::class);
            $this->app['events']->listen(PermissionUpdated::class, SecurityLogger::class);
            $this->app['events']->listen(ProcessCreated::class, SecurityLogger::class);
            $this->app['events']->listen(ProcessArchived::class, SecurityLogger::class);
            $this->app['events']->listen(ProcessPublished::class, SecurityLogger::class);
            $this->app['events']->listen(ProcessRestored::class, SecurityLogger::class);
            $this->app['events']->listen(ProcessUpdated::class, SecurityLogger::class);
            $this->app['events']->listen(QueueManagementAccessed::class, SecurityLogger::class);
            $this->app['events']->listen(RequestAction::class, SecurityLogger::class);
            $this->app['events']->listen(RequestError::class, SecurityLogger::class);
            $this->app['events']->listen(ScreenCreated::class, SecurityLogger::class);
            $this->app['events']->listen(ScreenDeleted::class, SecurityLogger::class);
            $this->app['events']->listen(ScreenUpdated::class, SecurityLogger::class);
            $this->app['events']->listen(ScriptCreated::class, SecurityLogger::class);
            $this->app['events']->listen(ScriptDeleted::class, SecurityLogger::class);
            $this->app['events']->listen(ScriptDuplicated::class, SecurityLogger::class);
            $this->app['events']->listen(ScriptExecutorCreated::class, SecurityLogger::class);
            $this->app['events']->listen(ScriptExecutorDeleted::class, SecurityLogger::class);
            $this->app['events']->listen(ScriptExecutorUpdated::class, SecurityLogger::class);
            $this->app['events']->listen(ScriptUpdated::class, SecurityLogger::class);
            $this->app['events']->listen(SettingsUpdated::class, SecurityLogger::class);
            $this->app['events']->listen(SignalCreated::class, SecurityLogger::class);
            $this->app['events']->listen(SignalDeleted::class, SecurityLogger::class);
            $this->app['events']->listen(SignalUpdated::class, SecurityLogger::class);
            $this->app['events']->listen(TemplateCreated::class, SecurityLogger::class);
            $this->app['events']->listen(TemplateDeleted::class, SecurityLogger::class);
            $this->app['events']->listen(TemplatePublished::class, SecurityLogger::class);
            $this->app['events']->listen(TemplateUpdated::class, SecurityLogger::class);
            $this->app['events']->listen(TokenCreated::class, SecurityLogger::class);
            $this->app['events']->listen(TokenDeleted::class, SecurityLogger::class);
            $this->app['events']->listen(UnauthorizedAccessAttempt::class, SecurityLogger::class);
            $this->app['events']->listen(UserCreated::class, SecurityLogger::class);
            $this->app['events']->listen(UserDeleted::class, SecurityLogger::class);
            $this->app['events']->listen(UserGroupMembershipUpdated::class, SecurityLogger::class);
            $this->app['events']->listen(UserRestored::class, SecurityLogger::class);
            $this->app['events']->listen(UserUpdated::class, SecurityLogger::class);
        }
    }
}
