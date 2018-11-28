<?php

use Illuminate\Cache\CacheServiceProvider;
use Illuminate\Filesystem\FilesystemServiceProvider;
use Illuminate\View\ViewServiceProvider;
use Illuminate\Database\DatabaseServiceProvider;
use ProcessMaker\Providers\RouteServiceProvider;

return [
    // The name of our application
    'name' => env('APP_NAME', 'ProcessMaker'),

    // The url of our host, will usually be set during installation
    'url' => env('APP_URL', 'http://localhost'),

    // The application key to be used for hashing secrets
    'key' => env('APP_KEY', 'base64:x80I9vQNxwllSuwBkTwfUa5qkgPTRdwqHCPSz7zHi1U='),

    // The encryption cipher to use, industry standard
    'cipher' => 'AES-256-CBC',

    // What environment are we running in. Local, prod, etc.
    'env' => env('APP_ENV', 'local'),

    // Should we place our application in debug mode
    'debug' => env('APP_DEBUG', false),

    // What's our default cache lifetype
    'cache_lifetime' => env('APP_CACHE_LIFETIME', 60),

    // The timezone for the application
    'timezone' => env('APP_TIMEZONE', 'America/Los_Angeles'),

    // The time format for the application
    'dateformat' => env('DATE_FORMAT', 'm/d/Y h:i A'),

    // The system locale
    'locale' => 'en',

    // The fallback locale
    'fallback_locale' => 'en',

    'disable_php_upload_execution' => env('DISABLE_PHP_UPLOAD_EXECUTION', 0),

    //Option Fractal, Serializer
    'serialize_fractal' => env('SERIALIZE_FRACTAL', \ProcessMaker\Transformers\ProcessMakerSerializer::class),

    //Option Fractal, paginator
    'paginate_fractal' => env('PAGINATE_FRACTAL', \League\Fractal\Pagination\IlluminatePaginatorAdapter::class),

    // The processmaker identifier of the web client application
    'web_client_application_id' => env('PM_CLIENT_ID', 'x-pm-local-client'),

    // The processmaker BPM scripts configuration
    'bpm_scripts_home' => env('BPM_SCRIPTS_HOME', '/home/vagrant'),
    'bpm_scripts_docker' => env('BPM_SCRIPTS_DOCKER', '/usr/bin/docker'),
    'bpm_scripts_docker_mode' => env('BPM_SCRIPTS_DOCKER_MODE', 'binding'),

    'providers' => [
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,


        /**
         * ProcessMaker Providers
         */
        ProcessMaker\Providers\ProcessMakerServiceProvider::class,
        ProcessMaker\Providers\AuthServiceProvider::class,
        ProcessMaker\Providers\EventServiceProvider::class,
        ProcessMaker\Providers\RouteServiceProvider::class,
        ProcessMaker\Providers\BroadcastServiceProvider::class,
        ProcessMaker\Providers\WorkflowServiceProvider::class,
        Laravel\Passport\PassportServiceProvider::class,
        Collective\Html\HtmlServiceProvider::class,

    ],


    'aliases' => [
        'App' => Illuminate\Support\Facades\App::class,
        'Artisan' => Illuminate\Support\Facades\Artisan::class,
        'Auth' => Illuminate\Support\Facades\Auth::class,
        'Blade' => Illuminate\Support\Facades\Blade::class,
        'Broadcast' => Illuminate\Support\Facades\Broadcast::class,
        'Bus' => Illuminate\Support\Facades\Bus::class,
        'Cache' => Illuminate\Support\Facades\Cache::class,
        'Config' => Illuminate\Support\Facades\Config::class,
        'Cookie' => Illuminate\Support\Facades\Cookie::class,
        'Crypt' => Illuminate\Support\Facades\Crypt::class,
        'DB' => Illuminate\Support\Facades\DB::class,
        'Eloquent' => Illuminate\Database\Eloquent\Model::class,
        'Event' => Illuminate\Support\Facades\Event::class,
        'File' => Illuminate\Support\Facades\File::class,
        'Gate' => Illuminate\Support\Facades\Gate::class,
        'Hash' => Illuminate\Support\Facades\Hash::class,
        'Lang' => Illuminate\Support\Facades\Lang::class,
        'Log' => Illuminate\Support\Facades\Log::class,
        'Mail' => Illuminate\Support\Facades\Mail::class,
        'Notification' => Illuminate\Support\Facades\Notification::class,
        'Password' => Illuminate\Support\Facades\Password::class,
        'Queue' => Illuminate\Support\Facades\Queue::class,
        'Redirect' => Illuminate\Support\Facades\Redirect::class,
        'Redis' => Illuminate\Support\Facades\Redis::class,
        'Request' => Illuminate\Support\Facades\Request::class,
        'Response' => Illuminate\Support\Facades\Response::class,
        'Route' => Illuminate\Support\Facades\Route::class,
        'Schema' => Illuminate\Support\Facades\Schema::class,
        'Session' => Illuminate\Support\Facades\Session::class,
        'Storage' => Illuminate\Support\Facades\Storage::class,
        'URL' => Illuminate\Support\Facades\URL::class,
        'Validator' => Illuminate\Support\Facades\Validator::class,
        'View' => Illuminate\Support\Facades\View::class,
        'Form' => Collective\Html\FormFacade::class,
        'Html' => Collective\Html\HtmlFacade::class,

        /**
         * ProcessMaker specific Facades
         */
        'WorkspaceManager' => ProcessMaker\Facades\WorkspaceManager::class,
        'SkinManager' => ProcessMaker\Facades\SkinManager::class,

        /**
         * Other Facades
         */
        'Theme' => Igaster\LaravelTheme\Facades\Theme::class,
        'Menu'      => Lavary\Menu\Facade::class,


    ],


];
