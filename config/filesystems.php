<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'imports' => [ // does not appear to be used any more
            'driver' => 'local',
            'root' => storage_path('app/imports'),
        ],

        'keys' => [ // does not appear to be used any more
            'driver' => 'local',
            'root' => env('KEYS_PATH') ? base_path(env('KEYS_PATH')) : storage_path('keys'),
        ],

        'mailtemplates' => [
            'driver' => 'local',
            'root' => env('MAILTEMPLATES_PATH') ? base_path(env('MAILTEMPLATES_PATH')) : storage_path('mailTemplates'),
        ],

        'process_templates' => [ // unit tests
            'driver' => 'local',
            'root' => env('PROCESS_TEMPLATES_PATH') ? base_path(env('PROCESS_TEMPLATES_PATH')) : database_path('processes/templates'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

        'profile' => [
            'driver' => 'local',
            'root' => storage_path('app/public/profile'),
            'url' => env('APP_URL') . '/storage/profile',
            'visibility' => 'public',
        ],

        'settings' => [
            'driver' => 'local',
            'root' => storage_path('app/public/setting'),
            'url' => env('APP_URL') . '/storage/setting',
            'visibility' => 'public',
        ],

        'private_settings' => [ // package-auth
            'driver' => 'local',
            'root' => storage_path('app/private/settings'),
            'visibility' => 'private',
        ],

        'web_services' => [ // package-data-sources, SoapConfigBuilder.php in core
            'driver' => 'local',
            'root' => storage_path('app/private/web_services'),
            'visibility' => 'private',
        ],

        'tmp' => [ // package-webentry, package-ai
            'driver' => 'local',
            'root' => storage_path('app/public/tmp'),
            'url' => env('APP_URL') . '/storage/tmp',
            'visibility' => 'public',
        ],

        'samlidp' => [
            'driver' => 'local',
            'root' => storage_path() . '/samlidp',
        ],

        'decision_tables' => [ // package-decision-engine
            'driver' => 'local',
            'root' => storage_path('decision-tables'),
            'url' => env('APP_URL') . '/storage/decision-tables',
            'visibility' => 'private',
        ],

        'lang' => [
            'driver' => 'local',
            'root' => lang_path(),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
