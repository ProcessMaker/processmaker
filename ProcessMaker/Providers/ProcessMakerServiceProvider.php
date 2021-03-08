<?php

namespace ProcessMaker\Providers;

use Blade;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Validator;
use ProcessMaker\Managers\IndexManager;
use ProcessMaker\Managers\LoginManager;
use ProcessMaker\Managers\ModelerManager;
use ProcessMaker\Managers\PackageManager;
use ProcessMaker\Managers\ScreenBuilderManager;
use ProcessMaker\Managers\ScriptBuilderManager;
use ProcessMaker\Events\ScreenBuilderStarting;
use Laravel\Horizon\Horizon;
use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use ProcessMaker\Models\AnonymousUser;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCollaboration;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\Setting;
use ProcessMaker\Models\User;
use ProcessMaker\Observers\ProcessCollaborationObserver;
use ProcessMaker\Observers\ProcessObserver;
use ProcessMaker\Observers\ProcessRequestObserver;
use ProcessMaker\Observers\SettingObserver;
use ProcessMaker\Observers\UserObserver;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Observers\ProcessRequestTokenObserver;
use ProcessMaker\PolicyExtension;

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
        Setting::observe(SettingObserver::class);
        Process::observe(ProcessObserver::class);
        ProcessRequest::observe(ProcessRequestObserver::class);
        ProcessRequestToken::observe(ProcessRequestTokenObserver::class);
        ProcessCollaboration::observe(ProcessCollaborationObserver::class);

        // Laravy Menu
        Blade::directive('lavaryMenuJson', function ($menu) {
            return "<?php echo htmlentities(lavaryMenuJson({$menu}), ENT_QUOTES); ?>";
        });

        //Custom validator for process, scripts, etc names (just alphanumeric, space, apostrophe or dash characters)
        Validator::extend('alpha_spaces', function ($attr, $val) {
            return preg_match('/^[\pL\s\-\_\d\.\']+$/u', $val);
        });

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
        
        $this->app->singleton(LoginManager::class, function () {
            return new LoginManager();
        });

        /**
         * Maps our Index Manager as a singleton. The Index Manager is used
         * to manage customizations to the search indexer.
         */
        $this->app->singleton(IndexManager::class, function () {
            return new IndexManager();
        });
        $this->app->make(IndexManager::class)->add('Requests', \ProcessMaker\Models\ProcessRequest::class);
        $this->app->make(IndexManager::class)->add('Tasks', \ProcessMaker\Models\ProcessRequestToken::class);

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

        /**
         * Maps our Script Builder Manager as a singleton. The Script builder Manager is used
         * to manage customizations to the Process Script Builder.
         */
        $this->app->singleton(ScriptBuilderManager::class, function($app) {
            return new ScriptBuilderManager();
        });

        $this->app->singleton(GlobalScriptsManager::class, function($app) {
            return new GlobalScriptsManager();
        });
        
        $this->app->singleton(AnonymousUser::class, function($app) {
            return AnonymousUser::where('username', AnonymousUser::ANONYMOUS_USERNAME)->firstOrFail();
        });
        
        $this->app->singleton(PolicyExtension::class, function($app) {
            return new PolicyExtension();
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

        // Log Notifications
        Event::listen(\Illuminate\Notifications\Events\NotificationSent::class, function($event) {
            \Log::debug(
                "Sent Notification to " .
                get_class($event->notifiable) .
                " #" . $event->notifiable->id .
                ": " . get_class($event->notification)
            );
        });

        // Log Broadcasts (messages sent to laravel-echo-server and redis)
        Event::listen(\Illuminate\Notifications\Events\BroadcastNotificationCreated::class, function($event) {
            $channels = implode(", ", $event->broadcastOn());
            \Log::debug("Broadcasting Notification " . $event->broadcastType() . "on channel(s) " . $channels);
        });

        //Enable
        Horizon::auth(function ($request) {
            return !empty(Auth::user());
        });

        // we are using custom passport migrations
        Passport::ignoreMigrations();
    }
}
