<?php

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

return [

    'cache_lifetime' => env('APP_CACHE_LIFETIME', 60),

    'dateformat' => env('DATE_FORMAT', 'm/d/Y h:i A'),

    'api_timeout' => env('API_TIMEOUT', 5000),

    'disable_php_upload_execution' => env('DISABLE_PHP_UPLOAD_EXECUTION', 0),

    'serialize_fractal' => env('SERIALIZE_FRACTAL', ProcessMaker\Transformers\ProcessMakerSerializer::class),

    'paginate_fractal' => env('PAGINATE_FRACTAL', League\Fractal\Pagination\IlluminatePaginatorAdapter::class),

    'web_client_application_id' => env('PM_CLIENT_ID', 'x-pm-local-client'),

    'processmaker_scripts_home' => env('PROCESSMAKER_SCRIPTS_HOME', __DIR__ . '/../storage/app'),

    'processmaker_scripts_docker' => env('PROCESSMAKER_SCRIPTS_DOCKER', '/usr/bin/docker'),

    'processmaker_scripts_docker_mode' => env('PROCESSMAKER_SCRIPTS_DOCKER_MODE', 'binding'),

    'processmaker_scripts_docker_host' => env('PROCESSMAKER_SCRIPTS_DOCKER_HOST', ''),

    'processmaker_scripts_docker_params' => env('PROCESSMAKER_SCRIPTS_DOCKER_PARAMS', ''),

    'processmaker_scripts_timeout' => env('PROCESSMAKER_SCRIPTS_TIMEOUT', 'timeout'),

    'processmaker_system_scripts_timeout_seconds' => env('PROCESSMAKER_SYSTEM_SCRIPTS_TIMEOUT_SECONDS', 300),

    'timer_events_seconds' => env('TIMER_EVENTS_SECONDS', 'truncate'),

    'bpmn_actions_max_lock_time' => (int) env('BPMN_ACTIONS_MAX_LOCK_TIME', 60),

    'bpmn_actions_max_lock_timeout' => (int) env('BPMN_ACTIONS_MAX_LOCK_TIMEOUT', 60000),

    'bpmn_actions_lock_check_interval' => (int) env('BPMN_ACTIONS_LOCK_CHECK_INTERVAL', 1000),

    'docker_host_url' => env(
        'DOCKER_HOST_URL',
        preg_replace(
            '/(\w+):\/\/([^:\/]+)(\:\d+)?/',
            '$1://172.17.0.1$3',
            env('APP_URL', 'http://localhost')
        )
    ),

    'nayra_rest_api_host' => env('NAYRA_REST_API_HOST', ''),

    'screen_task_cache_time' => env('SCREEN_TASK_CACHE_TIME', 86400),

    'api_ssl_verify' => env('API_SSL_VERIFY', 'true'),

    'instance' => env('DB_DATABASE'),

    'open_ai_nlq_to_pmql' => env('OPEN_AI_NLQ_TO_PMQL_ENABLED', false) && env('OPEN_AI_SECRET', false),

    'open_ai_process_translations' => env('OPEN_AI_PROCESS_TRANSLATIONS_ENABLED', false) &&
        env('OPEN_AI_SECRET', false),

    'ai_microservice_host' => env('AI_MICROSERVICE_HOST'),

    'security_log' => env('SECURITY_LOG', 'true'),

    'security_log_s3_uri' => env('SECURITY_LOG_S3_URI', 'security-logs'),

    'pm_analytics_dashboard' => env('PM_ANALYTICS_DASHBOARD', 'https://localhost'),

    'pm_analytics_chart' => env('PM_ANALYTICS_CHART', 'https://localhost'),

    'enable_default_sso' => env('ENABLE_DEFAULT_SSO', 'true'),

    'message_broker_driver' => env('MESSAGE_BROKER_DRIVER', 'default'),

    'configuration_debug_mode' => env('CONFIGURATION_DEBUG_MODE', false),

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

    'recommendations_enabled' => env('RECOMMENDATIONS_ENABLED', true),

    'login_view' => env('LOGIN_VIEW', 'auth.newLogin'),


    'aliases' => Facade::defaultAliases()->merge([
        'Agent' => Jenssegers\Agent\Facades\Agent::class,
        'Docker' => ProcessMaker\Facades\Docker::class,
        'ElasticScoutDriver\Factories\SearchRequestFactory' => ProcessMaker\Factories\SearchRequestFactory::class,
        'GlobalScripts' => ProcessMaker\Facades\GlobalScripts::class,
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

    'screen' => [
        'cache_enabled' => env('SCREEN_CACHE_ENABLED', false),
        'cache_timeout' => env('SCREEN_CACHE_TIMEOUT', 5000), // timeout in milliseconds
    ],

    'queue_imports' => env('QUEUE_IMPORTS', true),

    'node_bin_path' => env('NODE_BIN_PATH', '/usr/bin/node'),

    'task_drafts_enabled' => env('TASK_DRAFTS_ENABLED', true),

    'force_https' => env('FORCE_HTTPS', true),

    'nayra_docker_network' => env('NAYRA_DOCKER_NETWORK', 'host'),

    'process_request_errors_rate_limit' => env('PROCESS_REQUEST_ERRORS_RATE_LIMIT', 1),

    'process_request_errors_rate_limit_duration' => env('PROCESS_REQUEST_ERRORS_RATE_LIMIT_DURATION', 86400),

    'default_colors' => [
        'primary' => '#2773F3',
        'secondary' => '#728092',
        'success' => '#0CA442',
        'info' => '#104A75',
        'warning' => '#EC8E00',
        'danger' => '#EC5962',
        'dark' => '#20242A',
        'light' => '#FFFFFF',
    ],

    'encrypted_data' => [
        'driver' => env('ENCRYPTED_DATA_DRIVER', 'local'),
        'key' => env('ENCRYPTED_DATA_KEY', ''),
        'vault_host' => env('ENCRYPTED_DATA_VAULT_HOST', ''),
        'vault_token' => env('ENCRYPTED_DATA_VAULT_TOKEN', ''),
        'vault_transit_key' => env('ENCRYPTED_DATA_VAULT_TRANSIT_KEY', ''),
    ],

];
