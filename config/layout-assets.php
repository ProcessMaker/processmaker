<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Layout asset profiles
    |--------------------------------------------------------------------------
    |
    | Each profile defines which JavaScript bundles and optional vendors are
    | loaded by layouts/layout.blade.php. Extend profiles here when adding
    | new lightweight pages.
    |
    */
    'profiles' => [
        'default' => [
            'app' => 'js/app.js',
            'app_layout' => 'js/app-layout.js',
            'modeler_vendor' => true,
            'monaco' => true,
        ],
        'inbox' => [
            'app' => 'js/app-core.js',
            'app_layout' => 'js/app-layout-core.js',
            'modeler_vendor' => false,
            'monaco' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Route to profile mapping
    |--------------------------------------------------------------------------
    |
    | Keys are profile names. Values are patterns accepted by Request::is().
    | First matching profile wins; unmatched routes use "default".
    |
    */
    'routes' => [
        'inbox' => [
            'inbox',
            'inbox/*',
            'tasks',
        ],
    ],
];
