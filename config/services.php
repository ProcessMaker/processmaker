<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, ProcessmakerPost and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'ses' => [
        'key' => env('SES_KEY'),
        'secret' => env('SES_SECRET'),
        'region' => env('SES_REGION', 'us-east-1'),
    ],

    'processmakerpost' => [
        'secret' => env('PROCESSMAKERPOST_SECRET'),
    ],

    'open_ai' => [
        'secret' => env('OPEN_AI_SECRET'),
    ],

    'github' => [
        'base_url' => 'https://raw.githubusercontent.com/processmaker/',
        'template_repo' => env('DEFAULT_TEMPLATE_REPO', 'process-templates'),
        'template_branch' => env('DEFAULT_TEMPLATE_BRANCH', 'main'),
        'template_categories' => env('DEFAULT_TEMPLATE_CATEGORIES', 'accounting-and-finance,customer-success,human-resources,marketing-and-sales,operations,it'),
    ],
];
