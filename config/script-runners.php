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
    'php' => ProcessMaker\ScriptRunners\PhpRunner::class,
    'lua' => ProcessMaker\ScriptRunners\LuaRunner::class,
];
