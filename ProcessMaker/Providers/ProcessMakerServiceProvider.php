<?php

namespace ProcessMaker\Providers;

use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\PackageManifest;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Notifications\Events\BroadcastNotificationCreated;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades;
use Illuminate\Support\Facades\URL;
use Laravel\Dusk\DuskServiceProvider;
use Laravel\Horizon\Horizon;
use Laravel\Passport\Passport;
use Lavary\Menu\Menu;
use ProcessMaker\Console\Migration\ExtendedMigrateCommand;
use ProcessMaker\Events\ActivityAssigned;
use ProcessMaker\Events\ScreenBuilderStarting;
use ProcessMaker\Helpers\PmHash;
use ProcessMaker\ImportExport\Extension;
use ProcessMaker\ImportExport\SignalHelper;
use ProcessMaker\Jobs\SmartInbox;
use ProcessMaker\LicensedPackageManifest;
use ProcessMaker\Managers;
use ProcessMaker\Managers\MenuManager;
use ProcessMaker\Models;
use ProcessMaker\Observers;
use ProcessMaker\PolicyExtension;
use ProcessMaker\SchemaBuilderDecorator;

/**
 * Provide our ProcessMaker specific services.
 */
class ProcessMakerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(Menu::class, function ($app) {
            return new MenuManager();
        });

        $this->app->extend('db.schema', function ($schema) {
            return new SchemaBuilderDecorator($schema);
        });

        static::bootObservers();

        static::extendValidators();

        static::extendDrivers();

        static::forceHttps();

        $this->setupFactories();

        parent::boot();
    }

    public function register(): void
    {
        // Dusk, if env is appropriate
        // TODO Remove Dusk references and remove from composer dependencies
        if (!$this->app->environment('production')) {
            $this->app->register(DuskServiceProvider::class);
        }

        $this->app->singleton(Managers\PackageManager::class, function () {
            return new Managers\PackageManager();
        });

        $this->app->singleton(Managers\LoginManager::class, function () {
            return new Managers\LoginManager();
        });

        /*
         * Maps our Index Manager as a singleton. The Index Manager is used
         * to manage customizations to the search indexer.
         */
        $this->app->singleton(Managers\IndexManager::class, function () {
            return new Managers\IndexManager();
        });

        $this->app->make(Managers\IndexManager::class)
                  ->add('Requests', Models\ProcessRequest::class);

        $this->app->make(Managers\IndexManager::class)
                  ->add('Tasks', Models\ProcessRequestToken::class);

        /*
         * Maps our Modeler Manager as a singleton. The Modeler Manager is used
         * to manage customizations to the Process Modeler.
         */
        $this->app->singleton(Managers\ModelerManager::class, function ($app) {
            return new Managers\ModelerManager();
        });

        /*
         * Maps our Screen Builder Manager as a singleton. The Screen Builder Manager is used
         * to manage customizations to the Screen Builder.
         */
        $this->app->singleton(Managers\ScreenBuilderManager::class, function ($app) {
            return new Managers\ScreenBuilderManager();
        });

        /*
         * Maps our Script Builder Manager as a singleton. The Script builder Manager is used
         * to manage customizations to the Process Script Builder.
         */
        $this->app->singleton(Managers\ScriptBuilderManager::class, function ($app) {
            return new Managers\ScriptBuilderManager();
        });

        $this->app->singleton(PmHash::class, function () {
            return new PmHash();
        });

        $this->app->singleton(Models\RequestDevice::class, function () {
            return new Models\RequestDevice();
        });

        /*
         * Maps our Docker Manager as a singleton. The Docker Manager is used
         * to manage docker execution over the application.
         */
        $this->app->singleton(Managers\DockerManager::class, function ($app) {
            return new Managers\DockerManager();
        });

        $this->app->singleton(Managers\GlobalScriptsManager::class, function ($app) {
            return new Managers\GlobalScriptsManager();
        });

        $this->app->singleton(Models\AnonymousUser::class, function ($app) {
            return Models\AnonymousUser::where('username', '=', Models\AnonymousUser::ANONYMOUS_USERNAME)
                                       ->firstOrFail();
        });

        $this->app->singleton(PolicyExtension::class, function ($app) {
            return new PolicyExtension();
        });

        // Define the Import/Export Extension object as a singleton
        $this->app->singleton(Extension::class);

        // Define the SignalHelper object as a singleton
        $this->app->singleton(SignalHelper::class);

        // Register app-level events
        static::registerEvents();

        // Miscellaneous vendor customization
        static::configureVendors();

        $this->app->singleton(PackageManifest::class, fn () => new LicensedPackageManifest(
            new Filesystem, $this->app->basePath(), $this->app->getCachedPackagesPath()
        ));

        $this->app->extend(MigrateCommand::class, function () {
            return new ExtendedMigrateCommand(
                app('migrator'),
                app('events')
            );
        });
    }

    /**
     * Register app-level events.
     */
    protected static function registerEvents(): void
    {
        // Listen to the events for our core screen
        // types and add our javascript
        Facades\Event::listen(ScreenBuilderStarting::class, function ($event) {
            // Add any extensions to form builder
            // and renderer from packages
            $event->manager->addPackageScripts($event->type);

            switch ($event->type) {
                case 'FORM':
                    $event->manager->addScript(mix('js/processes/screen-builder/typeForm.js'));

                    break;

                case 'DISPLAY':
                    $event->manager->addScript(mix('js/processes/screen-builder/typeDisplay.js'));

                    break;
            }
        });

        // Log Notifications
        Facades\Event::listen(NotificationSent::class, function ($event) {
            $id = $event->notifiable->id;
            $notifiable = get_class($event->notifiable);
            $notification = get_class($event->notification);

            Facades\Log::debug("Sent Notification to {$notifiable} #{$id}: {$notification}");
        });

        // Log Broadcasts (messages sent to laravel-echo-server and redis)
        Facades\Event::listen(BroadcastNotificationCreated::class, function ($event) {
            $channels = implode(', ', $event->broadcastOn());

            Facades\Log::debug('Broadcasting Notification ' . $event->broadcastType() . 'on channel(s) ' . $channels);
        });

        // Fire job when task is assigned to a user
        Facades\Event::listen(ActivityAssigned::class, function ($event) {
            $task_id = $event->getProcessRequestToken()->id;
            // Dispatch the SmartInbox job with the processRequestToken as parameter
            SmartInbox::dispatch($task_id);
        });
    }

    /**
     * Bind and boot model observers.
     */
    protected static function bootObservers(): void
    {
        Models\User::observe(Observers\UserObserver::class);

        Models\Setting::observe(Observers\SettingObserver::class);

        Models\Process::observe(Observers\ProcessObserver::class);

        Models\ProcessRequest::observe(Observers\ProcessRequestObserver::class);

        Models\ProcessRequestToken::observe(Observers\ProcessRequestTokenObserver::class);

        Models\ProcessCollaboration::observe(Observers\ProcessCollaborationObserver::class);
    }

    /**
     * Register and extend existing validators.
     */
    protected static function extendValidators(): void
    {
        // Laravy Menu
        Facades\Blade::directive('lavaryMenuJson', function ($menu) {
            return "<?php echo htmlentities(lavaryMenuJson({$menu}), ENT_QUOTES); ?>";
        });

        // Custom validator for process, scripts, etc.
        // names (just alphanumeric, space, apostrophe
        // or dash characters)
        Facades\Validator::extend('alpha_spaces', function ($attr, $val) {
            return preg_match('/^[\pL\s\-\_\d\.\']+$/u', $val);
        });

        // Custom validator for variable names. Eg: correct var
        // names _myvar, myvar, myvar1, _myvar1. Eg: incorrect
        // variable names 1_myvar, 1myvar, myvar a
        Facades\Validator::extend('valid_variable', function ($attr, $val) {
            $key = explode('.', $attr)[1];

            return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $key);
        });
    }

    /**
     * Setup/configure various vendor services.
     */
    protected static function configureVendors(): void
    {
        // Allow user to view horizon queue dashboard
        Horizon::auth(function ($request) {
            return Facades\Auth::user() instanceof Models\User;
        });

        // we are using custom passport migrations
        Passport::ignoreMigrations();
    }

    private function setupFactories(): void
    {
        Factory::guessFactoryNamesUsing(function (string $modelName) {
            $factoryFinder = [
                [
                    '/ProcessMaker\\\\Plugins\\\\(.*?)\\\\Models\\\\(.*)/',
                    fn ($package, $baseName) => 'Database\\Factories\\ProcessMaker\\Plugins\\' . $package . '\\' . $baseName . 'Factory',
                ],
                [
                    '/ProcessMaker\\\\Packages\\\\Connectors\\\\(.*?)\\\\Models\\\\(.*)/',
                    fn ($package, $baseName) => 'Database\\Factories\\ProcessMaker\\Plugins\\' . $package . '\\' . $baseName . 'Factory',
                ],
                [
                    '/ProcessMaker\\\\Package\\\\(.*?)\\\\Models\\\\(.*)/',
                    fn ($package, $baseName) => 'Database\\Factories\\ProcessMaker\\Package\\' . $package . '\\' . $baseName . 'Factory',
                ],
                [
                    '/ProcessMaker\\\\Models\\\\(.*)/',
                    fn ($baseName) => 'Database\\Factories\\ProcessMaker\\Models\\' . $baseName . 'Factory',
                ],
            ];

            $factory = null;
            foreach ($factoryFinder as $matcher) {
                if (preg_match($matcher[0], $modelName, $match)) {
                    $factory = $matcher[1]($match[1], $match[2] ?? null);
                    break;
                }
            }

            return $factory;
        });

        Factory::guessModelNamesUsing(function ($factory) {
            $modelFinder = [
                [
                    '/Database\\\\Factories\\\\ProcessMaker\\\\Plugins\\\\(.*?)\\\\(.*)Factory/',
                    function ($package, $baseName) {
                        $model = 'ProcessMaker\\Plugins\\' . $package . '\\Models\\' . $baseName;
                        if (class_exists($model)) {
                            return $model;
                        } else {
                            return 'ProcessMaker\\Packages\\Connectors\\' . $package . '\\Models\\' . $baseName;
                        }
                    },
                ],
                [
                    '/Database\\\\Factories\\\\ProcessMaker\\\\Package\\\\(.*?)\\\\(.*)Factory/',
                    fn ($package, $baseName) => 'ProcessMaker\\Package\\' . $package . '\\Models\\' . $baseName,
                ],
                [
                    '/Database\\\\Factories\\\\(.*)Factory/',
                    fn ($match) => $match,
                ],
            ];

            $model = null;
            foreach ($modelFinder as $matcher) {
                if (preg_match($matcher[0], get_class($factory), $match)) {
                    $model = $matcher[1]($match[1], $match[2] ?? null);
                    break;
                }
            }

            return $model;
        });
    }

    protected static function extendDrivers(): void
    {
        Facades\Hash::extend('pm', function () {
            return resolve(PmHash::class);
        });
    }

    /**
     * Force HTTPS schema if configured to do so.
     */
    public static function forceHttps(): void
    {
        if (config('app.force_https')) {
            URL::forceScheme('https');
        }
    }
}
