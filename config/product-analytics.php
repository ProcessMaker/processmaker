<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enabled
    |--------------------------------------------------------------------------
    |
    | This setting determines whether this package should be enabled at all.
    |
    */

    'enabled' => env('PRODUCT_ANALYTICS_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Bypass Permissions Checks
    |--------------------------------------------------------------------------
    |
    | This setting determines whether permissions checks should be bypassed
    | so that all users can be tracked or whether only users with the
    | following permissions should be tracked: edit processes, edit
    | screens, edit scripts, edit vocabularies, edit data
    | connectors, edit collections, update settings,
    | edit users, edit groups.
    |
    */
    
    'bypass' => env('PRODUCT_ANALYTICS_BYPASS', false),

    /*
    |--------------------------------------------------------------------------
    | Services
    |--------------------------------------------------------------------------
    |
    | These settings configure specific analytics services.
    |
    */

    'services' => [

        'logrocket' => [
            'enabled' => env('PRODUCT_ANALYTICS_LOGROCKET_ENABLED', false),
            'app_id' => env('PRODUCT_ANALYTICS_LOGROCKET_APP_ID', 'gbuoqe/processmaker-4'),
            'sanitize' => [
                'inputs' => env('PRODUCT_ANALYTICS_LOGROCKET_SANITIZE_INPUTS', false),
                'text' => env('PRODUCT_ANALYTICS_LOGROCKET_SANITIZE_TEXT', false),
            ],
            'visitor' => [
                'id' => env('PRODUCT_ANALYTICS_LOGROCKET_VISITOR_ID', '{{uuid}}'),
                'name' => env('PRODUCT_ANALYTICS_LOGROCKET_VISITOR_NAME', '{{firstname}} {{lastname}}'),
                'email' => env('PRODUCT_ANALYTICS_LOGROCKET_VISITOR_EMAIL', '{{email}}'),
                'company' => env('PRODUCT_ANALYTICS_LOGROCKET_VISITOR_COMPANY', 'ProcessMaker'),
                'env' => env('PRODUCT_ANALYTICS_LOGROCKET_VISITOR_ENV', 'Dev'),
                'product' => env('PRODUCT_ANALYTICS_LOGROCKET_VISITOR_PRODUCT', 'Core'),
                'admin' => env('PRODUCT_ANALYTICS_LOGROCKET_VISITOR_ADMIN', '{{is_administrator}}'),
            ],
        ],

    ],

];
