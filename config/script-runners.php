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
        'name' => 'PHP',
        'mime_type' => 'application/x-php',
        'runner' => ProcessMaker\ScriptRunners\PhpRunner::class,
        'image' => env('SCRIPTS_PHP_IMAGE', 'processmaker/executor:php'),
    ],
    'lua' => [
        'name' => 'Lua',
        'mime_type' => 'application/x-lua',
        'runner' => ProcessMaker\ScriptRunners\LuaRunner::class,
        'image' => env('SCRIPTS_LUA_IMAGE', 'processmaker/executor:lua'),
    ],
];
