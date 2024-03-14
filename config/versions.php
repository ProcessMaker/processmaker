<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Autosave Configuration
    |--------------------------------------------------------------------------
    |
    | This section is for storing the configuration for autosave functionality.
    |
    */

    'delay' => [
        'script' => env('AUTOSAVE_SCRIPT_DELAY', 5000),
        'process' => env('AUTOSAVE_PROCESS_DELAY', 5000),
        'screen' => env('AUTOSAVE_SCREEN_DELAY', 5000),
    ],

];
