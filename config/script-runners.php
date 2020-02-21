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
    'lua' => [
        'name' => 'Lua',
        'runner' => 'LuaRunner',
        'mime_type' => 'application/x-lua',
        'image' => env('SCRIPTS_LUA_IMAGE', 'processmaker4/executor-lua'),
    ],
    'javascript' => [
        'name' => 'JavaScript',
        'runner' => 'NodeRunner',
        'mime_type' => 'text/javascript',
        'image' => env('SCRIPTS_NODE_IMAGE', 'processmaker4/executor-node'),
        'options' => ['gitRepoId' => 'sdk-node']
    ],
];
