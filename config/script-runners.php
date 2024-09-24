<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Script Runners
    |--------------------------------------------------------------------------
    |
    | These have been moved to executor packages. See:
    | https://github.com/ProcessMaker/docker-executor-php
    | https://github.com/ProcessMaker/docker-executor-lua
    | https://github.com/ProcessMaker/docker-executor-node
    |
    */
    'php-nayra' => [
        'name' => 'PHP (µService)',
        'runner' => 'PhpRunner',
        'mime_type' => 'application/x-php',
        'options' => ['invokerPackage' => 'ProcessMaker\\Client'],
        'package_path' => base_path('/docker-services/nayra'),
        'init_dockerfile' => [
            'WORKDIR /opt/executor/src',
        ],
        'final_instructions' => [
            'WORKDIR /app',
        ],
        'package_version' => '1.0.0',
        'sdk' => '',
    ],
];
