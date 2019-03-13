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
        'runner' => 'PhpRunner',
        'mime_type' => 'application/x-php',
        'image' => env('SCRIPTS_PHP_IMAGE', 'processmaker/executor:php'),
    ],
    'lua' => [
        'name' => 'Lua',
        'runner' => 'LuaRunner',
        'mime_type' => 'application/x-lua',
        'image' => env('SCRIPTS_LUA_IMAGE', 'processmaker/executor:lua'),
    ],
];
