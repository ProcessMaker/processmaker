<?php

namespace ProcessMaker\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use ProcessMaker\Managers\DatabaseManager;
use ProcessMaker\Managers\InputDocumentManager;
use ProcessMaker\Managers\OutputDocumentManager;
use ProcessMaker\Managers\ProcessCategoryManager;
use ProcessMaker\Managers\ProcessFileManager;
use ProcessMaker\Managers\ProcessManager;
use ProcessMaker\Managers\ReportTableManager;
use ProcessMaker\Managers\SchemaManager;
use ProcessMaker\Managers\TaskAssigneeManager;
use ProcessMaker\Managers\TaskManager;
use ProcessMaker\Managers\TasksDelegationManager;
use ProcessMaker\Managers\UserManager;
use ProcessMaker\Managers\ModelerManager;
use ProcessMaker\Managers\ScreenBuilderManager;
use ProcessMaker\Events\ScreenBuilderStarting;
use Laravel\Horizon\Horizon;
use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Eloquent\Relations\Relation;
use ProcessMaker\Models\User;
use ProcessMaker\Models\Group;

/**
 * Provide our ProcessMaker specific services
 * @package ProcessMaker\Providers
 */
class ProcessMakerServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap ProcessMaker services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }

    /**
     * Register our bindings in the service container
     */
    public function register()
    {
        // Dusk, if env is appropriate
        if(!$this->app->environment('production')) {
            $this->app->register(\Laravel\Dusk\DuskServiceProvider::class);
        }
        $this->app->singleton('user.manager', function ($app) {
            return new UserManager();
        });

        $this->app->singleton('process_file.manager', function ($app) {
            return new ProcessFileManager();
        });

        $this->app->singleton('process_category.manager', function ($app) {
            return new ProcessCategoryManager();
        });

        $this->app->singleton('database.manager', function ($app) {
            return new DatabaseManager();
        });

        $this->app->singleton('schema.manager', function ($app) {
            return new SchemaManager();
        });

        $this->app->singleton('process.manager', function ($app) {
            return new ProcessManager();
        });

        $this->app->singleton('report_table.manager', function ($app) {
            return new ReportTableManager();
        });

        $this->app->singleton('task.manager', function ($app) {
            return new TaskManager();
        });

        $this->app->singleton('task_assignee.manager', function ($app) {
            return new TaskAssigneeManager();
        });

        $this->app->singleton('input_document.manager', function ($app) {
            return new InputDocumentManager();
        });

        $this->app->singleton('output_document.manager', function ($app) {
            return new OutputDocumentManager();
        });

        $this->app->singleton('task_delegation.manager', function ($app) {
            return new TasksDelegationManager();
        });

        /**
         * Maps our Modeler Manager as a singleton. The Modeler Manager is used
         * to manage customizations to the Process Modeler.
         */
        $this->app->singleton(ModelerManager::class, function($app) {
            return new ModelerManager();
        });

        /**
         * Maps our Screen Builder Manager as a singleton. The Screen Builder Manager is used
         * to manage customizations to the Screen Builder.
         */
        $this->app->singleton(ScreenBuilderManager::class, function($app) {
            return new ScreenBuilderManager();
        });
        // Listen to the events for our core screen types and add our javascript
        Event::listen(ScreenBuilderStarting::class, function($event) {
            switch($event->type) {
                case 'FORM':
                    $event->manager->addScript(mix('js/processes/screen-builder/typeForm.js'));
                    break;
                case 'DISPLAY':
                    $event->manager->addScript(mix('js/processes/screen-builder/typeDisplay.js'));
                    break;
            }
        });


        //Enable
        Horizon::auth(function ($request) {
            return !empty(Auth::user());
        });

        // we are using custom passport migrations
        Passport::ignoreMigrations();
    }
}
