<?php

namespace ProcessMaker\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use ProcessMaker\Managers\ModelerManager;
use ProcessMaker\Managers\PackageManager;
use ProcessMaker\Managers\ScreenBuilderManager;
use ProcessMaker\Events\ScreenBuilderStarting;
use Laravel\Horizon\Horizon;
use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCollaboration;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\User;
use ProcessMaker\Observers\ProcessCollaborationObserver;
use ProcessMaker\Observers\ProcessObserver;
use ProcessMaker\Observers\ProcessRequestObserver;
use ProcessMaker\Observers\UserObserver;

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
        User::observe(UserObserver::class);
        Process::observe(ProcessObserver::class);
        ProcessRequest::observe(ProcessRequestObserver::class);
        ProcessCollaboration::observe(ProcessCollaborationObserver::class);

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

        $this->app->singleton(PackageManager::class, function () {
            return new PackageManager();
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
            // Add any extensions to form builder/renderer from packages
            $event->manager->addPackageScripts($event->type);

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
