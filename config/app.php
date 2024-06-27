<?php

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

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

    // What's our default cache lifecycle
    'cache_lifetime' => env('APP_CACHE_LIFETIME', 60),

    // The timezone for the application
    'timezone' => env('APP_TIMEZONE', 'America/Los_Angeles'),

    // The time format for the application
    'dateformat' => env('DATE_FORMAT', 'm/d/Y h:i A'),

    // The system locale
    'locale' => env('APP_LANG', 'en'),

    // The fallback locale
    'fallback_locale' => 'en',

    // The timeout length for API calls, in milliseconds (0 for no timeout)
    'api_timeout' => env('API_TIMEOUT', 5000),

    // Disables PHP execution in the storage directory
    // TODO Is this config value still used anywhere? :)
    'disable_php_upload_execution' => env('DISABLE_PHP_UPLOAD_EXECUTION', 0),

    // Option Fractal, Serializer
    // TODO Does the ProcessMakerSerializer class exist, if so, we need to fix its namespace :)
    'serialize_fractal' => env('SERIALIZE_FRACTAL', ProcessMaker\Transformers\ProcessMakerSerializer::class),

    // Option Fractal, paginator
    'paginate_fractal' => env('PAGINATE_FRACTAL', League\Fractal\Pagination\IlluminatePaginatorAdapter::class),

    // The processmaker identifier of the web client application
    'web_client_application_id' => env('PM_CLIENT_ID', 'x-pm-local-client'),

    // The processmaker BPM scripts configuration
    'processmaker_scripts_home' => env('PROCESSMAKER_SCRIPTS_HOME', __DIR__ . '/../storage/app'),

    // Path to docker executable
    'processmaker_scripts_docker' => env('PROCESSMAKER_SCRIPTS_DOCKER', '/usr/bin/docker'),

    // Docker mode with scripts
    'processmaker_scripts_docker_mode' => env('PROCESSMAKER_SCRIPTS_DOCKER_MODE', 'binding'),

    // tcp/other protocol uri to the docker host
    'processmaker_scripts_docker_host' => env('PROCESSMAKER_SCRIPTS_DOCKER_HOST', ''),

    // Default parameters for docker run command
    'processmaker_scripts_docker_params' => env('PROCESSMAKER_SCRIPTS_DOCKER_PARAMS', ''),

    // Default timeout for scripts
    'processmaker_scripts_timeout' => env('PROCESSMAKER_SCRIPTS_TIMEOUT', 'timeout'),

    // System-level scripts timeout
    'processmaker_system_scripts_timeout_seconds' => env('PROCESSMAKER_SYSTEM_SCRIPTS_TIMEOUT_SECONDS', 300),

    // Since the task scheduler has a preset of one minute (crontab), the times
    // must be rounded or truncated to the nearest HH:MM:00 before compare
    'timer_events_seconds' => env('TIMER_EVENTS_SECONDS', 'truncate'),

    // Table locks threshold for process_request_lock table
    'bpmn_actions_max_lock_time' => (int) env('BPMN_ACTIONS_MAX_LOCK_TIME', 60),

    // Maximum time to wait for a lock to be released. Default 60000 [ms]
    // If the processes are going to have thousands of concurrent parallel instances, increase this number.
    'bpmn_actions_max_lock_timeout' => (int) env('BPMN_ACTIONS_MAX_LOCK_TIMEOUT', 60000),

    // Lock check interval. Default every second. 1000 [ms]
    'bpmn_actions_lock_check_interval' => (int) env('BPMN_ACTIONS_LOCK_CHECK_INTERVAL', 1000),

    // The url of our host from inside the docker
    'docker_host_url' => env(
        'DOCKER_HOST_URL',
        preg_replace(
            '/(\w+):\/\/([^:\/]+)(\:\d+)?/',
            '$1://172.17.0.1$3',
            env('APP_URL', 'http://localhost')
        )
    ),

    // Allows our script executors to ignore invalid SSL. This should only be set to false for development.
    'api_ssl_verify' => env('API_SSL_VERIFY', 'true'),

    // Unique name on multi-tenant installations. Just use the DB name for now
    'instance' => env('DB_DATABASE'),

    // Allows to detect if OpenAI is enabled or not
    'open_ai_nlq_to_pmql' => env('OPEN_AI_NLQ_TO_PMQL_ENABLED', false) && env('OPEN_AI_SECRET', false),

    'open_ai_process_translations' => env('OPEN_AI_PROCESS_TRANSLATIONS_ENABLED', false) &&
        env('OPEN_AI_SECRET', false),

    // Microservice AI Host
    'ai_microservice_host' => env('AI_MICROSERVICE_HOST'),

    // Security log
    'security_log' => env('SECURITY_LOG', 'true'),

    // Security log custom S3 URI
    'security_log_s3_uri' => env('SECURITY_LOG_S3_URI', 'security-logs'),

    // PM Analytics Dashboard
    'pm_analytics_dashboard' => env('PM_ANALYTICS_DASHBOARD', 'https://localhost'),

    // PM Analytics Chart
    'pm_analytics_chart' => env('PM_ANALYTICS_CHART', 'https://localhost'),

    // Enable default SSO
    'enable_default_sso' => env('ENABLE_DEFAULT_SSO', 'true'),

    // Message broker driver to use in Workflow Manager
    'message_broker_driver' => env('MESSAGE_BROKER_DRIVER', 'default'),

    // When true, halt process execution if certain configuration settings are missing
    'configuration_debug_mode' => env('CONFIGURATION_DEBUG_MODE', false),

    // Global app settings
    'settings' => [

        // Path to logo image to be used on login page
        'login_logo_path' => env('LOGIN_LOGO_PATH', '/img/processmaker-login.svg'),

        // Path to site-wide logo image
        'main_logo_path' => env('MAIN_LOGO_PATH', '/img/processmaker-logo.svg'),

        // Path to site-wide icon
        'icon_path' => env('ICON_PATH_PATH', '/img/processmaker-icon.svg'),

        // Path to site-wide favicon
        'favicon_path' => env('FAVICON_PATH', '/img/favicon.svg'),

    ],

    // Define the view of the Login
    'login_view' => env('LOGIN_VIEW', 'auth.newLogin'),

    'providers' => ServiceProvider::defaultProviders()->merge([
        /**
         * Package Service Providers
         */
        Laravel\Passport\PassportServiceProvider::class,
        Laravel\Scout\ScoutServiceProvider::class,
        Collective\Html\HtmlServiceProvider::class,
        TeamTNT\Scout\TNTSearchScoutServiceProvider::class,
        Jenssegers\Agent\AgentServiceProvider::class,

        /**
         * ProcessMaker Service Providers
         */
        ProcessMaker\Providers\ProcessMakerServiceProvider::class,
        ProcessMaker\Providers\SettingServiceProvider::class,
        ProcessMaker\Providers\AuthServiceProvider::class,
        ProcessMaker\Providers\EventServiceProvider::class,
        ProcessMaker\Providers\HorizonServiceProvider::class,
        ProcessMaker\Providers\TelescopeServiceProvider::class,
        ProcessMaker\Providers\RouteServiceProvider::class,
        ProcessMaker\Providers\BroadcastServiceProvider::class,
        ProcessMaker\Providers\WorkflowServiceProvider::class,
        ProcessMaker\Providers\UpgradeServiceProvider::class,
        ProcessMaker\Providers\OauthMailServiceProvider::class,
        ProcessMaker\Providers\OpenAiServiceProvider::class,
        ProcessMaker\Providers\LicenseServiceProvider::class,
    ])->toArray(),

    'aliases' => Facade::defaultAliases()->merge([
        'Agent' => Jenssegers\Agent\Facades\Agent::class,
        'Docker' => ProcessMaker\Facades\Docker::class,
        'ElasticScoutDriver\Factories\SearchRequestFactory' => ProcessMaker\Factories\SearchRequestFactory::class,
        'Form' => Collective\Html\FormFacade::class,
        'GlobalScripts' => ProcessMaker\Facades\GlobalScripts::class,
        'Html' => Collective\Html\HtmlFacade::class,
        'Menu' => Lavary\Menu\Facade::class,
        'Redis' => Illuminate\Support\Facades\Redis::class,
        'RequestDevice' => ProcessMaker\Facades\RequestDevice::class,
        'SkinManager' => ProcessMaker\Facades\SkinManager::class,
        'Theme' => Igaster\LaravelTheme\Facades\Theme::class,
        'WorkspaceManager' => ProcessMaker\Facades\WorkspaceManager::class,
    ])->toArray(),

    'debug_blacklist' => [

        '_ENV' => [

            'DB_USERNAME',
            'DB_PASSWORD',
            'DATA_DB_PASSWORD',
            'DATA_DB_USERNAME',

        ],

        '_SERVER' => [

            'DB_USERNAME',
            'DB_PASSWORD',
            'DATA_DB_PASSWORD',
            'DATA_DB_USERNAME',

        ],

    ],

    // Screen builder options
    'screen' => [
        'cache_enabled' => env('SCREEN_CACHE_ENABLED', false),
        'cache_timeout' => env('SCREEN_CACHE_TIMEOUT', 5000), // timeout in milliseconds
    ],

    'queue_imports' => env('QUEUE_IMPORTS', true),

    'node_bin_path' => env('NODE_BIN_PATH', '/usr/bin/node'),

    'task_drafts_enabled' => env('TASK_DRAFTS_ENABLED', true),

    'force_https' => env('FORCE_HTTPS', true),
];
