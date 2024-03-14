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

    'autosave' => [
        'enabled' => filter_var(env('AUTOSAVE_DECISION_ENGINE_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN),
        'delay' => filter_var(env('AUTOSAVE_DECISION_ENGINE_EDIT_DELAY', 5000), FILTER_VALIDATE_INT),
    ],
];
