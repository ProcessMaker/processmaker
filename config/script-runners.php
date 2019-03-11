<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Script Runners
    |--------------------------------------------------------------------------
    |
    | This option controls the available Script Runners.
    |
    */
    'php' => [
        'runner' => ProcessMaker\ScriptRunners\PhpRunner::class,
        'image' => env('SCRIPTS_PHP_IMAGE', 'processmaker/executor:php'),
    ],
    'lua' => [
        'runner' => ProcessMaker\ScriptRunners\LuaRunner::class,
        'image' => env('SCRIPTS_LUA_IMAGE', 'processmaker/executor:lua'),
    ],
];
