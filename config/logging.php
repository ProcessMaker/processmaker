<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;

return [

    'deprecations' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),

    'channels' => [
        'test' => [
            'driver' => 'custom',
            'via' => ProcessMaker\Logging\CreateTestLogger::class,
        ],

        'single' => [
            'driver' => 'single',
            'path' => env('LOG_PATH', base_path('storage/logs/processmaker.log')),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => env('LOG_PATH', base_path('storage/logs/processmaker.log')),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 7,
            'replace_placeholders' => true,
        ],

        'emergency' => [
            'path' => base_path('storage/logs/laravel.log'),
        ],

        'data-source' => [
            'driver' => 'daily',
            'path' => base_path('storage/logs/data-source.log'),
            'level' => 'debug',
            'days' => env('DATA_SOURCE_CLEAR_LOG', 7),
        ],
    ],

];
