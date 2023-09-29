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

    'enabled' => env('PRODUCT_ANALYTICS_INTERCOM_ENABLED', false),
    'app_id' => env('PRODUCT_ANALYTICS_INTERCOM_APP_ID', null),
    'identity_key' => env('PRODUCT_ANALYTICS_INTERCOM_IDENTITY_KEY', null),
    'company' => env('PRODUCT_ANALYTICS_INTERCOM_COMPANY', 'ProcessMaker'),
    'env' => env('PRODUCT_ANALYTICS_INTERCOM_ENV', 'Dev'),
    'product' => env('PRODUCT_ANALYTICS_INTERCOM_PRODUCT', 'Platform'),    
];
