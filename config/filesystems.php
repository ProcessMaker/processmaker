<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DRIVER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Default Cloud Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Many applications store files both locally and in the cloud. For this
    | reason, you may specify a default "cloud" driver here. This driver
    | will be bound as the Cloud disk implementation in the container.
    |
    */

    'cloud' => env('FILESYSTEM_CLOUD', 's3'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'imports' => [
            'driver' => 'local',
            'root' => storage_path('app/imports'),
        ],

        'keys' => [
            'driver' => 'local',
            'root' => env('KEYS_PATH') ? base_path(env('KEYS_PATH')) : storage_path('keys'),
        ],

        'mailtemplates' => [
            'driver' => 'local',
            'root' => env('MAILTEMPLATES_PATH') ? base_path(env('MAILTEMPLATES_PATH')) : storage_path('mailTemplates'),
        ],

        'process_templates' => [
            'driver' => 'local',
            'root' => env('PROCESS_TEMPLATES_PATH') ? base_path(env('PROCESS_TEMPLATES_PATH')) : database_path('processes/templates'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
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

        'private_settings' => [
            'driver' => 'local',
            'root' => storage_path('app/private/settings'),
            'visibility' => 'private',
        ],

        'tmp' => [
            'driver' => 'local',
            'root' => storage_path('app/public/tmp'),
            'url' => env('APP_URL') . '/storage/tmp',
            'visibility' => 'public',
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
