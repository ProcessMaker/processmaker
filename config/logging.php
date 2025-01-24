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
            'path' => storage_path('logs/processmaker.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/processmaker.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 7,
            'replace_placeholders' => true,
        ],

        'data-source' => [
            'driver' => 'daily',
            'path' => storage_path('logs/data-source.log'),
            'level' => 'debug',
            'days' => env('DATA_SOURCE_CLEAR_LOG', 7),
        ],
    ],

];
