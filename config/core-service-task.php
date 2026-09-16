<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Core Service Task (in-process PHP)
    |--------------------------------------------------------------------------
    |
    | Opt-in path to run trusted PHP scripts without Docker/microservice.
    | Disabled by default. Only scripts with allow_in_process=true can run.
    |
    */
    'enabled' => env('CORE_SERVICE_TASK_ENABLED', false),

    'default_timeout' => (int) env('CORE_SERVICE_TASK_DEFAULT_TIMEOUT', 60),

    'max_timeout' => (int) env('CORE_SERVICE_TASK_MAX_TIMEOUT', 300),

    'memory_limit' => env('CORE_SERVICE_TASK_MEMORY_LIMIT', '256M'),

    'php_binary' => env('CORE_SERVICE_TASK_PHP_BINARY', PHP_BINARY),

    /*
    |--------------------------------------------------------------------------
    | Execution mode
    |--------------------------------------------------------------------------
    |
    | modules: run in the Laravel worker with registered ScriptRuntime modules
    |          (packages call ScriptRuntime::registerModule(...)).
    | bare:    legacy PHP subprocess without Laravel/autoload (no modules).
    |
    */
    'execution' => env('CORE_SERVICE_TASK_EXECUTION', 'modules'),

    /*
    | Optional dedicated queue name for RunServiceTask nodes that set
    | config.queue. Defaults to bpmn when not overridden on the node.
    */
    'queue' => env('CORE_SERVICE_TASK_QUEUE', 'bpmn'),
];
